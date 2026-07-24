<?php
namespace App\Http\Middleware;
use App\Models\Admin;
use App\Models\AdminSession;
use Closure;
use Illuminate\Http\Request;
class EnsureAdminAuthenticated { public function handle(Request $request, Closure $next) { $admin=Admin::with('role')->where('id',$request->session()->get('admin_id'))->where('is_active',true)->first(); if(!$admin && $request->cookie('admin_session')) { $session=AdminSession::with('admin.role')->where('token',hash('sha256',$request->cookie('admin_session')))->where('expires_at','>',now())->first(); if($session && $session->admin->is_active) { $admin=$session->admin; $request->session()->put('admin_id',$admin->id); } } if(!$admin) return redirect()->route('admin.login')->with('error','กรุณาเข้าสู่ระบบสำหรับพนักงาน'); $request->attributes->set('admin',$admin); return $next($request); } }
