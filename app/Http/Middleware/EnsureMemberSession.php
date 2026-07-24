<?php
namespace App\Http\Middleware;
use App\Models\MemberSession;
use Closure;
use Illuminate\Http\Request;
class EnsureMemberSession { public function handle(Request $request, Closure $next) { $token=$request->cookie('member_session'); $session=$token ? MemberSession::with('member')->where('token',hash('sha256',$token))->where('expires_at','>',now())->first() : null; if(!$session) return redirect()->route('members.find')->with('error','กรุณาค้นหาสมาชิกก่อนจองคิว'); $request->attributes->set('member',$session->member); return $next($request); } }
