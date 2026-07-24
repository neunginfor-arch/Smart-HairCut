<?php
namespace App\Services;
use App\Models\Member;
use App\Models\MemberSession;
use Illuminate\Support\Str;
class MemberSessionService {
    public function start(Member $member): string { $token=Str::random(64); MemberSession::where('member_id',$member->id)->delete(); MemberSession::create(['member_id'=>$member->id,'token'=>hash('sha256',$token),'expires_at'=>now()->addMinutes(15)]); return $token; }
}
