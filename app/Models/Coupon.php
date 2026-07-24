<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Coupon extends Model { protected $fillable=['code','name','description','discount_type','discount_value','required_points','valid_from','valid_until','is_active']; protected $casts=['valid_from'=>'date','valid_until'=>'date','is_active'=>'boolean']; }
