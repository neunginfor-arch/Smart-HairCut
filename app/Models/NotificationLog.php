<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class NotificationLog extends Model { protected $fillable=['member_id','booking_id','channel','type','recipient','message','status','response','sent_at']; protected $casts=['sent_at'=>'datetime']; public function member(){return $this->belongsTo(Member::class);} public function booking(){return $this->belongsTo(Booking::class);} }
