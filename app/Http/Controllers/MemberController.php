<?php
namespace App\Http\Controllers;
use App\Models\Member;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Service;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\PointHistory;
use App\Models\Promotion;
use App\Services\MemberSessionService;
use App\Services\PaymentExpirationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class MemberController extends Controller {
 public function create(): View { return view('member.register'); }
 public function store(Request $request): RedirectResponse { $data=$request->validate(['first_name'=>'required|string|max:100','last_name'=>'required|string|max:100','phone'=>'required|string|max:20|unique:members,phone','birth_date'=>'required|date|before:today','gender'=>'required|in:male,female,other','email'=>'nullable|email|max:255']); Member::create($data); return redirect()->route('members.find')->with('success','สมัครสมาชิกสำเร็จ กรุณาค้นหาด้วยเบอร์โทรศัพท์'); }
 public function findForm(): View { return view('member.find'); }
 public function find(Request $request, MemberSessionService $sessions): RedirectResponse { $data=$request->validate(['phone'=>'required|string|max:20']); $member=Member::where('phone',$data['phone'])->first(); if(!$member) return back()->withInput()->with('not_found',true); $plainToken=$sessions->start($member); return redirect()->route('member.dashboard')->withCookie(cookie('member_session',$plainToken,15,null,null,(bool) config('session.secure'),true,false,'Lax')); }
 public function dashboard(Request $request, PaymentExpirationService $expiration): View { $expiration->expireDue(); $member=$request->attributes->get('member'); $upcoming=Booking::with(['branch','employee','service','payment'])->where('member_id',$member->id)->whereIn('status',['pending','confirmed'])->whereDate('booking_date','>=',today())->orderBy('booking_date')->orderBy('start_time')->first(); $couponUsages=CouponUsage::with('coupon')->where('member_id',$member->id)->whereNull('booking_id')->whereNull('used_at')->whereHas('coupon',fn($query)=>$query->where('is_active',true)->whereDate('valid_from','<=',today())->whereDate('valid_until','>=',today()))->latest()->get(); $promotions=Promotion::where('is_active',true)->whereDate('starts_at','<=',today())->whereDate('ends_at','>=',today())->orderBy('display_order')->orderByDesc('id')->get(); return view('member.dashboard',['member'=>$member,'upcoming'=>$upcoming,'points'=>PointHistory::where('member_id',$member->id)->sum('points'),'couponCount'=>Coupon::where('is_active',true)->whereDate('valid_from','<=',today())->whereDate('valid_until','>=',today())->count(),'branches'=>Branch::where('is_active',true)->get(),'employees'=>Employee::where('is_active',true)->get(),'services'=>Service::where('is_active',true)->get(),'couponUsages'=>$couponUsages,'promotions'=>$promotions]); }
 public function coupons(Request $request): View { $member=$request->attributes->get('member'); $coupons=Coupon::where('is_active',true)->whereDate('valid_from','<=',today())->whereDate('valid_until','>=',today())->orderBy('valid_until')->get(); $points=PointHistory::where('member_id',$member->id)->sum('points'); $redeemed=CouponUsage::where('member_id',$member->id)->whereNull('used_at')->pluck('coupon_id')->all(); return view('member.coupons',compact('member','coupons','points','redeemed')); }
 public function redeemCoupon(Request $request, Coupon $coupon): RedirectResponse { $member=$request->attributes->get('member'); if(!$coupon->is_active || !$coupon->required_points || $coupon->valid_from->isFuture() || $coupon->valid_until->isPast()) return back()->with('error','คูปองนี้ไม่พร้อมให้แลก'); try { \DB::transaction(function() use($member,$coupon){ Member::whereKey($member->id)->lockForUpdate()->firstOrFail(); if(CouponUsage::where('member_id',$member->id)->where('coupon_id',$coupon->id)->whereNull('used_at')->exists()) throw new \RuntimeException('คุณมีคูปองนี้อยู่แล้ว'); $balance=PointHistory::where('member_id',$member->id)->sum('points'); if($balance < $coupon->required_points) throw new \RuntimeException('คะแนนสะสมไม่เพียงพอ'); PointHistory::create(['member_id'=>$member->id,'type'=>'redeem','points'=>-$coupon->required_points,'description'=>'แลกคูปอง '.$coupon->code]); CouponUsage::create(['coupon_id'=>$coupon->id,'member_id'=>$member->id]); }); return back()->with('success','แลกคูปองสำเร็จ เลือกใช้คูปองนี้ในหน้าจองเพื่อหักส่วนลดได้ทันที'); } catch(\RuntimeException $e) { return back()->with('error',$e->getMessage()); } }
 public function points(Request $request): View { $member=$request->attributes->get('member'); return view('member.points',['member'=>$member,'points'=>PointHistory::where('member_id',$member->id)->sum('points'),'histories'=>PointHistory::with('booking')->where('member_id',$member->id)->latest()->paginate(15)]); }
}
