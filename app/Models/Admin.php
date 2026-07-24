<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Admin extends Model { protected $fillable=['role_id','name','email','password','is_active']; protected $hidden=['password']; public function role(): BelongsTo { return $this->belongsTo(Role::class); } }
