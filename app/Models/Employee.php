<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model { protected $fillable=['branch_id','name','phone','line_user_id','is_active']; public function branch(){return $this->belongsTo(Branch::class);} public function timeOffs(){return $this->hasMany(EmployeeTimeOff::class);} }
