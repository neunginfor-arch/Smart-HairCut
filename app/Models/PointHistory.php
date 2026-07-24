<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PointHistory extends Model { protected $fillable=['member_id','booking_id','type','points','sales_amount','description']; public function member(){return $this->belongsTo(Member::class);} public function booking(){return $this->belongsTo(Booking::class);} }
