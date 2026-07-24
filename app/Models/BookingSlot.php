<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BookingSlot extends Model { protected $fillable=['booking_id','employee_id','slot_date','slot_time']; }
