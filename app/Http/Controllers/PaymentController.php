<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\CouponUsage;
use App\Models\PointHistory;
use App\Models\Setting;
use App\Services\LineMessagingService;
use App\Services\PaymentExpirationService;
use App\Services\SlipVerificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    public function show(Request $request, Booking $booking, PaymentExpirationService $expiration): View|RedirectResponse
    {
        $this->ensureOwner($request, $booking);
        $booking->loadMissing(['member', 'branch', 'employee', 'service', 'payment.couponUsage.coupon']);
        if (!$booking->payment) {
            return redirect()->route('member.dashboard')->with('error', 'รายการจองนี้ไม่มีรายการชำระเงิน');
        }
        if ($expiration->expire($booking->payment)) {
            return redirect()->route('member.dashboard')->with('error', 'หมดเวลาชำระเงิน 10 นาที ระบบยกเลิกการจองและคืนช่วงเวลาแล้ว');
        }
        if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
            return redirect()->route('member.dashboard')->with('error', 'รายการจองนี้ไม่สามารถชำระเงินได้แล้ว');
        }

        return view('member.payment', [
            'booking' => $booking,
            'payment' => $booking->payment,
            'hasPaymentQr' => filled(Setting::valueFor('payment_qr_path')),
        ]);
    }

    public function verify(
        Request $request,
        Booking $booking,
        SlipVerificationService $slips,
        LineMessagingService $line,
        PaymentExpirationService $expiration
    ): RedirectResponse {
        $this->ensureOwner($request, $booking);

        $data = $request->validate([
            'slip' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'qr_data' => 'required|string|max:4096',
        ], [
            'slip.required' => 'กรุณาอัปโหลดรูปสลิป',
            'slip.image' => 'ไฟล์สลิปต้องเป็นรูปภาพ',
            'qr_data.required' => 'ไม่พบ QR Code ในรูปสลิป กรุณาใช้ภาพที่ชัดเจน',
        ]);

        $payment = $booking->payment()->firstOrFail();
        if ($expiration->expire($payment)) {
            return redirect()->route('member.dashboard')->with('error', 'หมดเวลาชำระเงิน 10 นาที ระบบยกเลิกการจองและคืนช่วงเวลาแล้ว');
        }
        if ($payment->status === 'verified' || $booking->status === 'confirmed') {
            return redirect()->route('member.dashboard')->with('success', 'รายการนี้ชำระเงินเรียบร้อยแล้ว');
        }
        if ($booking->status !== 'pending') {
            return redirect()->route('member.dashboard')->with('error', 'รายการจองนี้ไม่สามารถชำระเงินได้แล้ว');
        }
        if (Carbon::parse($booking->booking_date->format('Y-m-d').' '.$booking->start_time)->lessThanOrEqualTo(now())) {
            return redirect()->route('member.dashboard')->with('error', 'เลยเวลาของรายการจองนี้แล้ว กรุณาจองคิวใหม่');
        }

        $oldSlipPath = $payment->slip_path;
        $slipPath = $request->file('slip')->store('payment-slips');

        try {
            $verified = $slips->verify($data['qr_data'], $payment);
            $didVerify = false;
            DB::transaction(function () use ($booking, $payment, $slipPath, $verified, &$didVerify) {
                $lockedPayment = BookingPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($lockedPayment->status === 'verified') {
                    return;
                }
                if (!in_array($lockedPayment->status, ['pending', 'rejected'], true)
                    || !$lockedPayment->expires_at
                    || $lockedPayment->expires_at->isPast()) {
                    throw new \RuntimeException('payment_expired');
                }

                $lockedPayment->update($verified + [
                    'slip_path' => $slipPath,
                    'status' => 'verified',
                    'rejection_reason' => null,
                    'verified_at' => now(),
                ]);
                if ($lockedPayment->coupon_usage_id) {
                    $couponUsage = CouponUsage::whereKey($lockedPayment->coupon_usage_id)
                        ->lockForUpdate()
                        ->first();
                    if (!$couponUsage
                        || $couponUsage->member_id !== $booking->member_id
                        || $couponUsage->booking_id !== $booking->id
                        || $couponUsage->used_at !== null) {
                        throw new \RuntimeException('coupon_unavailable');
                    }
                    $couponUsage->update(['used_at' => now()]);
                }

                $points = (int) (floor((float) $lockedPayment->amount / 100) * 10);
                if ($points > 0) {
                    PointHistory::firstOrCreate(
                        ['booking_payment_id' => $lockedPayment->id],
                        [
                            'member_id' => $booking->member_id,
                            'booking_id' => $booking->id,
                            'type' => 'earn',
                            'points' => $points,
                            'sales_amount' => $lockedPayment->amount,
                            'description' => 'คะแนนจากยอดชำระ Booking '.$booking->booking_no,
                        ]
                    );
                }
                $booking->update(['status' => 'confirmed']);
                $didVerify = true;
            });
        } catch (ValidationException $exception) {
            $payment->update([
                'slip_path' => $slipPath,
                'status' => 'pending',
                'rejection_reason' => $exception->validator->errors()->first('slip'),
            ]);
            if ($oldSlipPath && $oldSlipPath !== $slipPath) {
                Storage::disk('local')->delete($oldSlipPath);
            }
            throw $exception;
        } catch (\RuntimeException $exception) {
            Storage::disk('local')->delete($slipPath);
            if ($exception->getMessage() === 'coupon_unavailable') {
                return back()->with('error', 'คูปองที่เลือกไม่พร้อมใช้งาน กรุณาสร้างรายการจองใหม่');
            }
            $expiration->expire($payment->fresh());
            return redirect()->route('member.dashboard')->with('error', 'หมดเวลาชำระเงิน 10 นาที ระบบยกเลิกการจองแล้ว');
        }

        if ($oldSlipPath && $oldSlipPath !== $slipPath) {
            Storage::disk('local')->delete($oldSlipPath);
        }

        $payment->refresh();
        if ($didVerify) {
            $line->sendPaymentConfirmation($booking->fresh(), $payment);
        }

        return redirect()->route('member.dashboard')
            ->with('success', 'ตรวจสอบการชำระเงินสำเร็จ ยืนยันหมายเลขการจอง '.$booking->booking_no.' แล้ว');
    }

    public function qrImage(): BinaryFileResponse
    {
        $path = Setting::valueFor('payment_qr_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=300',
            'Content-Type' => Storage::disk('local')->mimeType($path) ?: 'image/png',
        ]);
    }

    public function downloadQrImage(): BinaryFileResponse
    {
        $path = Setting::valueFor('payment_qr_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'png';

        return response()->download(
            Storage::disk('local')->path($path),
            'sm-hair-design-payment-qr.'.$extension,
            [
                'Cache-Control' => 'private, no-store',
                'Content-Type' => Storage::disk('local')->mimeType($path) ?: 'image/png',
            ]
        );
    }

    private function ensureOwner(Request $request, Booking $booking): void
    {
        abort_unless($booking->member_id === $request->attributes->get('member')->id, 404);
    }
}
