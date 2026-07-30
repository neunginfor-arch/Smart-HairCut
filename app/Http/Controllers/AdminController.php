<?php
namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminSession;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeTimeOff;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\PointHistory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\ShopClosure;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function login(): View { return view('admin.login'); }

    public function authenticate(Request $request): RedirectResponse
    {
        $data=$request->validate(['email'=>'required|email','password'=>'required|string','remember'=>'nullable|boolean']);
        $admin=Admin::where('email',$data['email'])->where('is_active',true)->first();
        if(!$admin || !Hash::check($data['password'],$admin->password)) return back()->withInput($request->only('email'))->withErrors(['email'=>'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
        $request->session()->regenerate(); $request->session()->put('admin_id',$admin->id);
        if($request->boolean('remember')) { $plainToken=Str::random(64); AdminSession::where('admin_id',$admin->id)->delete(); AdminSession::create(['admin_id'=>$admin->id,'token'=>hash('sha256',$plainToken),'expires_at'=>now()->addDays(30)]); return redirect()->route('admin.dashboard')->withCookie(cookie('admin_session',$plainToken,60*24*30,null,null,(bool)config('session.secure'),true,false,'Lax')); }
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse { $token=$request->cookie('admin_session'); if($token) AdminSession::where('token',hash('sha256',$token))->delete(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('admin.login')->withCookie(cookie()->forget('admin_session'))->with('success','ออกจากระบบแล้ว'); }

    public function dashboard(Request $request): View
    {
        $today=today();
        $stats=['members_today'=>Member::whereDate('created_at',$today)->count(),'bookings_today'=>Booking::whereDate('booking_date',$today)->whereNotIn('status',['cancelled','no_show'])->count(),'redeemed_discount_month'=>(float)BookingPayment::where('status','verified')->whereYear('verified_at',$today->year)->whereMonth('verified_at',$today->month)->sum('discount_amount'),'sales_today'=>(float)PointHistory::whereDate('created_at',$today)->whereNotNull('sales_amount')->sum('sales_amount'),'sales_month'=>(float)PointHistory::whereYear('created_at',$today->year)->whereMonth('created_at',$today->month)->whereNotNull('sales_amount')->sum('sales_amount'),'sales_year'=>(float)PointHistory::whereYear('created_at',$today->year)->whereNotNull('sales_amount')->sum('sales_amount')];
        return view('admin.dashboard',['stats'=>$stats,'recentBookings'=>Booking::with(['member','branch','employee','service'])->latest()->take(8)->get(),'admin'=>$request->attributes->get('admin')]);
    }

    public function members(): View { return view('admin.members',['members'=>Member::withCount('bookings')->latest()->paginate(12)]); }

    public function storeMember(Request $request): RedirectResponse { Member::create($this->memberData($request)); return redirect()->route('admin.members')->with('success','เพิ่มสมาชิกเรียบร้อยแล้ว'); }

    public function editMember(Member $member): View { return view('admin.member-form',['member'=>$member]); }

    public function updateMember(Request $request, Member $member): RedirectResponse { $member->update($this->memberData($request,$member)); return redirect()->route('admin.members')->with('success','อัปเดตข้อมูลสมาชิกเรียบร้อยแล้ว'); }

    public function destroyMember(Member $member): RedirectResponse
    {
        if($member->bookings()->exists()) return back()->with('error','ไม่สามารถลบสมาชิกที่มีประวัติการจองได้');
        $member->delete(); return back()->with('success','ลบสมาชิกเรียบร้อยแล้ว');
    }

    public function bookings(): View { return view('admin.bookings',['bookings'=>Booking::with(['member','branch','employee','service','payment'])->latest()->paginate(15)]); }

    public function checkIn(Booking $booking): RedirectResponse
    {
        if($booking->status!=='confirmed') return back()->with('error','ต้องยืนยันการชำระเงินก่อนจึงจะ Check-in ได้');
        $booking->update(['status'=>'checked_in','checked_in_at'=>now()]);
        return back()->with('success','Check-in '.$booking->booking_no.' เรียบร้อยแล้ว');
    }

    public function checkOut(Request $request, Booking $booking): RedirectResponse
    {
        if($booking->status!=='checked_in') return back()->with('error','ต้อง Check-in ก่อนจึงจะ Check-out ได้');
        $data=$request->validate(['sales_amount'=>'required|numeric|min:0|max:999999.99']);
        DB::transaction(function() use($booking,$data){
            $payment=BookingPayment::where('booking_id',$booking->id)->where('status','verified')->lockForUpdate()->first();
            $amount=$payment ? (float)$payment->amount : (float)$data['sales_amount'];
            $points=(int)(floor($amount/100)*10);
            $booking->update(['status'=>'completed','checked_out_at'=>now()]);
            if($points>0){
                $identity=$payment ? ['booking_payment_id'=>$payment->id] : ['booking_id'=>$booking->id,'type'=>'earn'];
                PointHistory::firstOrCreate($identity,['member_id'=>$booking->member_id,'booking_id'=>$booking->id,'type'=>'earn','points'=>$points,'sales_amount'=>$amount,'description'=>'คะแนนจากยอดชำระ Booking '.$booking->booking_no]);
            }
        });
        return back()->with('success','Check-out สำเร็จ ระบบตรวจสอบคะแนนจากยอดชำระเรียบร้อยแล้ว');
    }

    public function cancelBooking(Booking $booking): RedirectResponse
    {
        if(in_array($booking->status,['cancelled','completed','checked_in'],true)) return back()->with('error','รายการนี้ไม่สามารถยกเลิกได้');
        DB::transaction(function() use($booking){ BookingSlot::where('booking_id',$booking->id)->delete(); CouponUsage::where('booking_id',$booking->id)->whereNull('used_at')->update(['booking_id'=>null]); $booking->update(['status'=>'cancelled']); });
        return back()->with('success','ยกเลิกคิวและคืนช่วงเวลาเรียบร้อยแล้ว');
    }

    public function bookingSetup(): View { return view('admin.booking-setup',['branches'=>Branch::withCount('employees')->latest()->get(),'employees'=>Employee::with('branch')->latest()->get(),'services'=>Service::latest()->get(),'closures'=>ShopClosure::orderBy('start_date')->get(),'timeOffs'=>EmployeeTimeOff::with('employee.branch')->orderBy('start_at')->get(),'paymentQrPath'=>Setting::valueFor('payment_qr_path'),'paymentReceiverAccount'=>Setting::valueFor('payment_receiver_account'),'paymentReceiverName'=>Setting::valueFor('payment_receiver_name')]); }
    public function updatePaymentSettings(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'payment_qr'=>'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'receiver_account'=>'nullable|string|max:30',
            'receiver_name'=>'nullable|string|max:255',
        ]);

        if($request->hasFile('payment_qr')){
            $oldPath=Setting::valueFor('payment_qr_path');
            $newPath=$request->file('payment_qr')->store('payment-qr');
            Setting::put('payment_qr_path',$newPath);
            if($oldPath && $oldPath!==$newPath) Storage::disk('local')->delete($oldPath);
        }

        Setting::put('payment_receiver_account',preg_replace('/\D+/','',(string)($data['receiver_account'] ?? '')));
        Setting::put('payment_receiver_name',trim((string)($data['receiver_name'] ?? '')));

        return back()->with('success','อัปเดต QR และข้อมูลรับชำระเงินเรียบร้อยแล้ว');
    }
    public function storeShopClosure(Request $request): RedirectResponse { ShopClosure::create($this->shopClosureData($request)); return back()->with('success','บันทึกวันหยุดร้านเรียบร้อยแล้ว'); }
    public function updateShopClosure(Request $request, ShopClosure $shopClosure): RedirectResponse { $shopClosure->update($this->shopClosureData($request)); return back()->with('success','อัปเดตวันหยุดร้านเรียบร้อยแล้ว'); }
    public function destroyShopClosure(ShopClosure $shopClosure): RedirectResponse { $shopClosure->delete(); return back()->with('success','ลบวันหยุดร้านเรียบร้อยแล้ว'); }
    public function storeEmployeeTimeOff(Request $request): RedirectResponse { EmployeeTimeOff::create($this->employeeTimeOffData($request)); return back()->with('success','บันทึกวันหยุดพนักงานเรียบร้อยแล้ว'); }
    public function updateEmployeeTimeOff(Request $request, EmployeeTimeOff $employeeTimeOff): RedirectResponse { $employeeTimeOff->update($this->employeeTimeOffData($request)); return back()->with('success','อัปเดตวันหยุดพนักงานเรียบร้อยแล้ว'); }
    public function destroyEmployeeTimeOff(EmployeeTimeOff $employeeTimeOff): RedirectResponse { $employeeTimeOff->delete(); return back()->with('success','ลบวันหยุดพนักงานเรียบร้อยแล้ว'); }
    public function adminUsers(): View { return view('admin.users',['admins'=>Admin::with('role')->latest()->get(),'roles'=>Role::orderBy('id')->get()]); }
    public function storeAdminUser(Request $request): RedirectResponse { $data=$request->validate(['role_id'=>'required|exists:roles,id','name'=>'required|string|max:255','email'=>'required|email|max:255|unique:admins,email','password'=>'required|string|min:10|confirmed']); Admin::create(['role_id'=>$data['role_id'],'name'=>$data['name'],'email'=>$data['email'],'password'=>Hash::make($data['password']),'is_active'=>true]); return back()->with('success','เพิ่มบัญชีผู้ดูแลเรียบร้อยแล้ว'); }
    public function coupons(): View { return view('admin.coupons',['coupons'=>Coupon::latest()->get(),'pendingUsages'=>\App\Models\CouponUsage::with(['coupon','member'])->whereNull('booking_id')->whereNull('used_at')->latest()->get(),'redeemedUsages'=>\App\Models\CouponUsage::with(['coupon','member'])->latest()->get()]); }
    public function storeCoupon(Request $request): RedirectResponse { Coupon::create($this->couponData($request)); return back()->with('success','เพิ่มคูปองเรียบร้อยแล้ว'); }
    public function updateCoupon(Request $request, Coupon $coupon): RedirectResponse { $coupon->update($this->couponData($request)); return back()->with('success','อัปเดตคูปองเรียบร้อยแล้ว'); }
    public function destroyCoupon(Coupon $coupon): RedirectResponse { if(\App\Models\CouponUsage::where('coupon_id',$coupon->id)->exists()) return back()->with('error','ไม่สามารถลบคูปองที่มีประวัติการแลกหรือใช้งานได้'); $coupon->delete(); return back()->with('success','ลบคูปองเรียบร้อยแล้ว'); }
    public function confirmCouponUsage(Request $request, \App\Models\CouponUsage $couponUsage): RedirectResponse { if($couponUsage->used_at) return back()->with('error','คูปองนี้ถูกยืนยันใช้งานแล้ว'); if($couponUsage->booking_id) return back()->with('error','คูปองนี้ผูกกับรายการจองและจะยืนยันอัตโนมัติเมื่อชำระเงินสำเร็จ'); $couponUsage->update(['used_at'=>now(),'confirmed_by_admin_id'=>$request->attributes->get('admin')->id]); return back()->with('success','ยืนยันการใช้คูปองเรียบร้อยแล้ว'); }
    public function storeBranch(Request $request): RedirectResponse { Branch::create($this->branchData($request)); return back()->with('success','เพิ่มสาขาเรียบร้อยแล้ว'); }
    public function updateBranch(Request $request, Branch $branch): RedirectResponse { $branch->update($this->branchData($request)); return back()->with('success','อัปเดตสาขาเรียบร้อยแล้ว'); }
    public function destroyBranch(Branch $branch): RedirectResponse { if($branch->employees()->exists() || Booking::where('branch_id',$branch->id)->exists()) return back()->with('error','ไม่สามารถลบสาขาที่มีช่างหรือประวัติการจองได้'); $branch->delete(); return back()->with('success','ลบสาขาเรียบร้อยแล้ว'); }

    public function storeEmployee(Request $request): RedirectResponse { Employee::create($this->employeeData($request)); return back()->with('success','เพิ่มช่างเรียบร้อยแล้ว'); }
    public function updateEmployee(Request $request, Employee $employee): RedirectResponse { $employee->update($this->employeeData($request)); return back()->with('success','อัปเดตข้อมูลช่างเรียบร้อยแล้ว'); }
    public function destroyEmployee(Employee $employee): RedirectResponse { if(Booking::where('employee_id',$employee->id)->exists()) return back()->with('error','ไม่สามารถลบช่างที่มีประวัติการจองได้'); $employee->delete(); return back()->with('success','ลบช่างเรียบร้อยแล้ว'); }

    public function storeService(Request $request): RedirectResponse { Service::create($this->serviceData($request)); return back()->with('success','เพิ่มบริการเรียบร้อยแล้ว'); }
    public function updateService(Request $request, Service $service): RedirectResponse { $service->update($this->serviceData($request)); return back()->with('success','อัปเดตบริการเรียบร้อยแล้ว'); }
    public function destroyService(Service $service): RedirectResponse { if(Booking::where('service_id',$service->id)->exists()) return back()->with('error','ไม่สามารถลบบริการที่มีประวัติการจองได้'); $service->delete(); return back()->with('success','ลบบริการเรียบร้อยแล้ว'); }

    public function section(string $section): View
    {
        $config=match($section) {
            'members' => ['title'=>'จัดการสมาชิก','description'=>'รายชื่อสมาชิกที่สมัครผ่านระบบ','columns'=>['ชื่อสมาชิก','เบอร์โทร','อีเมล','จำนวนการจอง','สมัครเมื่อ'],'records'=>Member::withCount('bookings')->latest()->paginate(15),'map'=>fn($item)=>[$item->first_name.' '.$item->last_name,$item->phone,$item->email ?: '—',(string)$item->bookings_count,$item->created_at->format('d/m/Y H:i')]],
            'bookings' => ['title'=>'จัดการคิว','description'=>'รายการจองคิวทั้งหมด','columns'=>['Booking No.','ลูกค้า','สาขา / ช่าง','วันเวลา','สถานะ'],'records'=>Booking::with(['member','branch','employee'])->latest()->paginate(15),'map'=>fn($item)=>[$item->booking_no,$item->member->first_name.' '.$item->member->last_name,$item->branch->name.' / '.$item->employee->name,$item->booking_date->format('d/m/Y').' '.substr($item->start_time,0,5),$item->status]],
            'coupons' => ['title'=>'จัดการคูปอง','description'=>'คูปองและช่วงเวลาที่สามารถใช้งานได้','columns'=>['Code','ชื่อคูปอง','ส่วนลด','ใช้ได้ถึง','สถานะ'],'records'=>Coupon::latest()->paginate(15),'map'=>fn($item)=>[$item->code,$item->name,$item->discount_type==='percentage'?$item->discount_value.'%':'฿'.number_format($item->discount_value,2),$item->valid_until->format('d/m/Y'),$item->is_active?'ใช้งาน':'ปิดใช้งาน']],
            'points' => ['title'=>'จัดการคะแนน','description'=>'ประวัติการได้รับและใช้คะแนนสมาชิก','columns'=>['สมาชิก','ประเภท','คะแนน','ยอดขาย','วันที่'],'records'=>PointHistory::with('member')->latest()->paginate(15),'map'=>fn($item)=>[$item->member?->first_name.' '.$item->member?->last_name,$item->type,(string)$item->points,$item->sales_amount!==null?'฿'.number_format($item->sales_amount,2):'—',$item->created_at->format('d/m/Y H:i')]],
            'notifications' => ['title'=>'ประวัติแจ้งเตือน','description'=>'บันทึกการส่งข้อความผ่าน LINE Messaging API','columns'=>['ผู้รับ','ประเภท','ช่องทาง','สถานะ','รายละเอียด','ส่งเมื่อ'],'records'=>NotificationLog::with('member')->latest()->paginate(15),'map'=>fn($item)=>[$item->member?->first_name.' '.$item->member?->last_name ?: $item->recipient,$item->type,$item->channel,$item->status,$this->notificationDetail($item),$item->sent_at?->format('d/m/Y H:i') ?: 'ยังไม่ส่ง']],
        };
        $rows=$config['records']->getCollection()->map($config['map']);
        return view('admin.section',['section'=>$section,'title'=>$config['title'],'description'=>$config['description'],'columns'=>$config['columns'],'records'=>$config['records'],'rows'=>$rows]);
    }

    private function memberData(Request $request, ?Member $member=null): array
    {
        return $request->validate(['first_name'=>'required|string|max:100','last_name'=>'required|string|max:100','phone'=>'required|string|max:20|unique:members,phone,'.($member?->id ?? 'NULL'),'birth_date'=>'required|date|before:today','gender'=>'required|in:male,female,other','email'=>'nullable|email|max:255']);
    }
    private function branchData(Request $request): array { return $request->validate(['name'=>'required|string|max:255','address'=>'nullable|string','phone'=>'nullable|string|max:20','is_active'=>'nullable|boolean'])+['is_active'=>$request->boolean('is_active')]; }
    private function employeeData(Request $request): array { return $request->validate(['branch_id'=>'required|exists:branches,id','name'=>'required|string|max:255','phone'=>'nullable|string|max:20','line_user_id'=>'nullable|string|max:255','is_active'=>'nullable|boolean'])+['is_active'=>$request->boolean('is_active')]; }
    private function serviceData(Request $request): array { return $request->validate(['name'=>'required|string|max:255','duration_minutes'=>'required|integer|min:30|max:480','price'=>'required|numeric|min:0','is_active'=>'nullable|boolean'])+['is_active'=>$request->boolean('is_active')]; }
    private function notificationDetail(NotificationLog $notification): string
    {
        if($notification->status==='sent') return 'ส่งผ่าน LINE สำเร็จ';
        if($notification->status==='pending') return 'รอส่งข้อความ';
        if(!$notification->response) return 'ระบบไม่ส่งข้อความ กรุณาตรวจสอบการตั้งค่า LINE';

        $response=json_decode($notification->response,true);
        if(!is_array($response)) return Str::limit($notification->response,180);

        $message=(string)($response['message'] ?? 'LINE ไม่สามารถส่งข้อความได้');
        $detail=(string)($response['details'][0]['message'] ?? '');
        $property=(string)($response['details'][0]['property'] ?? '');
        return Str::limit(trim($message.($detail ? ' · '.$detail : '').($property ? ' '.$property : '')),180);
    }
    private function shopClosureData(Request $request): array { return $request->validate(['start_date'=>'required|date|after_or_equal:today','end_date'=>'required|date|after_or_equal:start_date','note'=>'nullable|string|max:255']); }
    private function employeeTimeOffData(Request $request): array
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date',
            'end_time' => 'required|date_format:H:i',
            'note' => 'nullable|string|max:255',
        ]);

        $startAt = Carbon::createFromFormat('Y-m-d H:i', $data['start_date'].' '.$data['start_time']);
        $endAt = Carbon::createFromFormat('Y-m-d H:i', $data['end_date'].' '.$data['end_time']);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw ValidationException::withMessages([
                'end_time' => 'วันและเวลาสิ้นสุดต้องอยู่หลังวันและเวลาเริ่มหยุด',
            ]);
        }

        return [
            'employee_id' => $data['employee_id'],
            'start_at' => $startAt,
            'end_at' => $endAt,
            'note' => $data['note'],
        ];
    }
    private function couponData(Request $request): array { return $request->validate(['code'=>'required|string|max:255|unique:coupons,code,'.($request->route('coupon')?->id ?? 'NULL'),'name'=>'required|string|max:255','description'=>'nullable|string','discount_type'=>'required|in:fixed,percentage','discount_value'=>'required|numeric|min:0','required_points'=>'nullable|integer|min:1','valid_from'=>'required|date','valid_until'=>'required|date|after_or_equal:valid_from','is_active'=>'nullable|boolean'])+['is_active'=>$request->boolean('is_active')]; }
}
