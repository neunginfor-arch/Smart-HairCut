<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Member extends Model { protected $fillable=['first_name','last_name','phone','birth_date','gender','email','line_user_id']; protected $casts=['birth_date'=>'date']; public function bookings(): HasMany { return $this->hasMany(Booking::class); } }
