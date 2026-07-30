<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPayment extends Model
{
    protected $fillable = [
        'booking_id',
        'subtotal',
        'discount_amount',
        'coupon_usage_id',
        'amount',
        'expires_at',
        'status',
        'slip_path',
        'transaction_ref',
        'transferred_at',
        'payer_name',
        'from_bank',
        'to_bank',
        'to_account_no',
        'verified_payload',
        'rejection_reason',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'transferred_at' => 'datetime',
        'verified_payload' => 'array',
        'verified_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function couponUsage()
    {
        return $this->belongsTo(CouponUsage::class);
    }
}
