<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CouponUsage extends Model { protected $fillable=['coupon_id','member_id','booking_id','confirmed_by_admin_id','used_at']; protected $casts=['used_at'=>'datetime']; public function coupon(){return $this->belongsTo(Coupon::class);} public function member(){return $this->belongsTo(Member::class);} }
