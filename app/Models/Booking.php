<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Booking extends Model { protected $fillable=['booking_no','member_id','branch_id','employee_id','service_id','booking_date','start_time','end_time','status','qr_token','checked_in_at','checked_out_at']; protected $casts=['booking_date'=>'date','checked_in_at'=>'datetime','checked_out_at'=>'datetime']; public function member(){return $this->belongsTo(Member::class);} public function branch(){return $this->belongsTo(Branch::class);} public function employee(){return $this->belongsTo(Employee::class);} public function service(){return $this->belongsTo(Service::class);} }
