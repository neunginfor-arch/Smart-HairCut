<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingSlot;
use App\Models\Branch;
use App\Models\CouponUsage;
use App\Models\Employee;
use App\Models\EmployeeTimeOff;
use App\Models\Service;
use App\Models\ShopClosure;
use App\Services\PaymentExpirationService;
use App\Services\LineMessagingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function create(Request $request): View
    {
        $member = $request->attributes->get('member');

        return view('member.booking', [
            'branches' => Branch::where('is_active', true)->get(),
            'employees' => Employee::where('is_active', true)->get(),
            'services' => Service::where('is_active', true)->get(),
            'couponUsages' => $this->availableCouponUsages($member->id),
        ]);
    }

    public function slots(Request $request, PaymentExpirationService $expiration): JsonResponse
    {
        $expiration->expireDue();

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $date = Carbon::parse($data['date'])->startOfDay();
        $closure = $this->closureFor($date);
        if ($closure) {
            return response()->json([
                'closed' => true,
                'message' => $this->closureMessage($date),
                'slots' => $this->closedSlots(),
            ]);
        }

        $employee = Employee::where('id', $data['employee_id'])->where('is_active', true)->first();
        if (!$employee) {
            return response()->json(['message' => 'ช่างที่เลือกไม่พร้อมให้บริการ'], 422);
        }

        $taken = BookingSlot::where('employee_id', $employee->id)
            ->where('slot_date', $data['date'])
            ->pluck('slot_time')
            ->map(fn ($time) => substr($time, 0, 5))
            ->all();

        $timeOffs = EmployeeTimeOff::where('employee_id', $employee->id)
            ->where('start_at', '<', $date->copy()->endOfDay())
            ->where('end_at', '>', $date->copy()->startOfDay())
            ->get();
        $slots = [];
        $isToday = $date->isToday();
        $currentTime = now()->format('H:i');

        for ($hour = 10; $hour < 19; $hour++) {
            $time = sprintf('%02d:00', $hour);
            $hasPassed = $isToday && $time <= $currentTime;
            $slotStart = $date->copy()->setTime($hour, 0);
            $slotEnd = $slotStart->copy()->addHour();
            $onLeave = $timeOffs->contains(fn (EmployeeTimeOff $timeOff) => $timeOff->start_at->lt($slotEnd) && $timeOff->end_at->gt($slotStart));
            $slots[] = ['time' => $time, 'available' => !$hasPassed && !$onLeave && !in_array($time, $taken, true)];
        }

        return response()->json(['closed' => false, 'slots' => $slots]);
    }

    public function availableEmployees(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $date = Carbon::parse($data['date'])->startOfDay();
        $workStart = $date->copy()->setTime(10, 0);
        $workEnd = $date->copy()->setTime(19, 0);

        $ids = Employee::where('branch_id', $data['branch_id'])
            ->where('is_active', true)
            ->whereDoesntHave('timeOffs', function ($query) use ($workStart, $workEnd) {
                $query->where('start_at', '<=', $workStart)
                    ->where('end_at', '>=', $workEnd);
            })
            ->pluck('id');

        return response()->json(['employee_ids' => $ids]);
    }

    public function store(Request $request, LineMessagingService $line): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'coupon_usage_id' => 'nullable|integer|exists:coupon_usages,id',
        ]);

        $bookingDate = Carbon::parse($data['booking_date'])->startOfDay();
        if ($this->closureFor($bookingDate)) {
            return back()->withInput()->withErrors(['start_time' => $this->closureMessage($bookingDate)]);
        }

        $member = $request->attributes->get('member');
        $service = Service::findOrFail($data['service_id']);
        $employee = Employee::where('id', $data['employee_id'])
            ->where('branch_id', $data['branch_id'])
            ->where('is_active', true)
            ->first();
        if (!$employee) {
            return back()->withInput()->withErrors(['employee_id' => 'ช่างที่เลือกไม่พร้อมให้บริการในสาขานี้ กรุณาเลือกช่างใหม่']);
        }
        $start = Carbon::parse($data['booking_date'].' '.$data['start_time']);
        $end = $start->copy()->addMinutes($service->duration_minutes);

        if ($start->lessThanOrEqualTo(now())) {
            return back()->withInput()->withErrors(['start_time' => 'ไม่สามารถจองช่วงเวลาที่ผ่านไปแล้ว']);
        }
        if ($start->hour < 10 || $end->greaterThan(Carbon::parse($data['booking_date'].' 19:00'))) {
            return back()->withInput()->withErrors(['start_time' => 'กรุณาเลือกเวลาในช่วง 10:00–19:00 น.']);
        }
        if ($this->employeeOnLeave($employee->id, $start, $end)) {
            return back()->withInput()->withErrors(['employee_id' => 'ช่างที่เลือกติดวันหยุดในช่วงเวลานี้ กรุณาเลือกช่างหรือเวลาใหม่']);
        }

        try {
            $booking = DB::transaction(function () use ($data, $member, $service, $employee, $start, $end) {
                $couponUsage = null;
                $subtotal = round((float) $service->price, 2);
                $discount = 0.0;

                if (!empty($data['coupon_usage_id'])) {
                    $couponUsage = CouponUsage::with('coupon')
                        ->whereKey($data['coupon_usage_id'])
                        ->lockForUpdate()
                        ->first();
                    if (!$couponUsage) {
                        throw new \RuntimeException('ไม่พบคูปองที่เลือก กรุณาเลือกคูปองใหม่');
                    }
                    $coupon = $couponUsage->coupon;
                    $available = $couponUsage->member_id === $member->id
                        && $couponUsage->booking_id === null
                        && $couponUsage->used_at === null
                        && $coupon
                        && $coupon->is_active
                        && !$coupon->valid_from->isFuture()
                        && !$coupon->valid_until->isPast();
                    if (!$available) {
                        throw new \RuntimeException('คูปองนี้ไม่พร้อมใช้งาน กรุณาเลือกคูปองใหม่');
                    }

                    $discount = $coupon->discount_type === 'percentage'
                        ? $subtotal * min((float) $coupon->discount_value, 100) / 100
                        : (float) $coupon->discount_value;
                    $discount = round(min($subtotal, max(0, $discount)), 2);
                }
                $payable = round(max(0, $subtotal - $discount), 2);

                $slotTimes = [];
                for ($time = $start->copy(); $time->lt($end); $time->addHour()) {
                    $slotTimes[] = $time->format('H:i:00');
                }

                foreach ($slotTimes as $slotTime) {
                    if (BookingSlot::where('employee_id', $employee->id)
                        ->where('slot_date', $data['booking_date'])
                        ->where('slot_time', $slotTime)
                        ->lockForUpdate()
                        ->exists()) {
                        throw new \RuntimeException('ช่วงเวลานี้ถูกจองไปแล้ว');
                    }
                }

                $booking = Booking::create([
                    'booking_no' => 'SC'.now()->format('Ymd').str_pad((string) (Booking::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT),
                    'member_id' => $member->id,
                    'branch_id' => $data['branch_id'],
                    'employee_id' => $employee->id,
                    'service_id' => $service->id,
                    'booking_date' => $data['booking_date'],
                    'start_time' => $start->format('H:i:s'),
                    'end_time' => $end->format('H:i:s'),
                    'status' => 'pending',
                    'qr_token' => Str::random(64),
                ]);

                foreach ($slotTimes as $slotTime) {
                    BookingSlot::create([
                        'booking_id' => $booking->id,
                        'employee_id' => $employee->id,
                        'slot_date' => $data['booking_date'],
                        'slot_time' => $slotTime,
                    ]);
                }

                $payment = BookingPayment::create([
                    'booking_id' => $booking->id,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'coupon_usage_id' => $couponUsage?->id,
                    'amount' => $payable,
                    'expires_at' => now()->addMinutes(10),
                    'status' => $payable > 0 ? 'pending' : 'verified',
                    'verified_at' => $payable > 0 ? null : now(),
                ]);

                if ($couponUsage) {
                    $couponUsage->update([
                        'booking_id' => $booking->id,
                        'used_at' => $payable > 0 ? null : now(),
                    ]);
                }
                if ($payable <= 0) {
                    $booking->update(['status' => 'confirmed']);
                }

                return $booking;
            });

            $booking->load(['member', 'branch', 'employee', 'service', 'payment']);
            if ($booking->payment?->status === 'verified') {
                $line->sendPaymentConfirmation($booking, $booking->payment);

                return redirect()->route('member.dashboard')
                    ->with('success', 'ใช้คูปองสำเร็จ ยอดชำระครั้งนี้ ฿0.00 และยืนยันการจองแล้ว');
            }

            return redirect()->route('payments.show', $booking)
                ->with('success', 'สร้างรายการจองแล้ว กรุณาชำระเงินเพื่อยืนยันคิว');
        } catch (\RuntimeException $exception) {
            return back()->withInput()->withErrors(['start_time' => $exception->getMessage()]);
        }
    }

    private function closureFor(Carbon $date): ?ShopClosure
    {
        return ShopClosure::whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->first();
    }

    private function nextAvailableDate(Carbon $from): Carbon
    {
        $candidate = $from->copy()->startOfDay();
        while ($this->closureFor($candidate)) {
            $candidate->addDay();
        }

        return $candidate;
    }

    private function closureMessage(Carbon $date): string
    {
        $nextDate = $this->nextAvailableDate($date->copy()->addDay());

        return 'ขออภัยวันนี้หยุดให้บริการ เจอกันได้ในวันที่ '.$nextDate->format('d/m/Y');
    }

    private function closedSlots(): array
    {
        return collect(range(10, 18))
            ->map(fn ($hour) => ['time' => sprintf('%02d:00', $hour), 'available' => false])
            ->all();
    }

    private function employeeOnLeave(int $employeeId, Carbon $start, Carbon $end): bool
    {
        return EmployeeTimeOff::where('employee_id', $employeeId)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();
    }

    private function availableCouponUsages(int $memberId)
    {
        return CouponUsage::with('coupon')
            ->where('member_id', $memberId)
            ->whereNull('booking_id')
            ->whereNull('used_at')
            ->whereHas('coupon', fn ($query) => $query
                ->where('is_active', true)
                ->whereDate('valid_from', '<=', today())
                ->whereDate('valid_until', '>=', today()))
            ->latest()
            ->get();
    }
}
