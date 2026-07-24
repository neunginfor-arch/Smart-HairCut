<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MemberSession extends Model { protected $fillable=['member_id','token','expires_at']; protected $casts=['expires_at'=>'datetime']; public function member(){ return $this->belongsTo(Member::class); } }
