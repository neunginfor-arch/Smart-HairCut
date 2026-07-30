<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PointHistory extends Model { protected $fillable=['member_id','booking_id','booking_payment_id','type','points','sales_amount','description']; protected $casts=['sales_amount'=>'decimal:2']; public function member(){return $this->belongsTo(Member::class);} public function booking(){return $this->belongsTo(Booking::class);} public function payment(){return $this->belongsTo(BookingPayment::class,'booking_payment_id');} }
