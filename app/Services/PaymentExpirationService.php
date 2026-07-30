<?php

namespace App\Services;

use App\Models\BookingPayment;
use App\Models\BookingSlot;
use App\Models\CouponUsage;
use Illuminate\Support\Facades\DB;

class PaymentExpirationService
{
    public function expire(BookingPayment $payment): bool
    {
        if (!in_array($payment->status, ['pending', 'rejected'], true)
            || !$payment->expires_at
            || $payment->expires_at->isFuture()) {
            return false;
        }

        return DB::transaction(function () use ($payment) {
            $locked = BookingPayment::with('booking')->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (!in_array($locked->status, ['pending', 'rejected'], true)
                || !$locked->expires_at
                || $locked->expires_at->isFuture()) {
                return false;
            }

            $booking = $locked->booking;
            if ($booking && $booking->status === 'pending') {
                BookingSlot::where('booking_id', $booking->id)->delete();
                CouponUsage::where('booking_id', $booking->id)
                    ->whereNull('used_at')
                    ->update(['booking_id' => null]);
                $booking->update(['status' => 'cancelled']);
            }

            $locked->update([
                'status' => 'rejected',
                'rejection_reason' => 'หมดเวลาชำระเงินภายใน 10 นาที',
            ]);

            return true;
        });
    }

    public function expireDue(): int
    {
        $expired = 0;

        BookingPayment::query()
            ->whereIn('status', ['pending', 'rejected'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($payments) use (&$expired) {
                foreach ($payments as $payment) {
                    if ($this->expire($payment)) {
                        $expired++;
                    }
                }
            });

        return $expired;
    }
}
