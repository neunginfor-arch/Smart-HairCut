<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdminSession extends Model { protected $fillable=['admin_id','token','expires_at']; protected $casts=['expires_at'=>'datetime']; public function admin(){return $this->belongsTo(Admin::class);} }
