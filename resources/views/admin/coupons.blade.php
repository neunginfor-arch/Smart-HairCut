@extends('layouts.app')

@section('content')
<section class="shell py-12">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-black tracking-widest text-brand">ADMIN CONSOLE</p>
            <h1 class="mt-2 text-4xl font-black">จัดการคูปองและคะแนนแลก</h1>
            <p class="mt-2 text-sm text-black/55 dark:text-white/55">กำหนดคูปองส่วนลดและคะแนนที่ลูกค้าต้องใช้เพื่อแลก</p>
        </div>
        <a class="btn border border-black/15 dark:border-white/20" href="{{ route('admin.dashboard') }}">← Admin Dashboard</a>
    </div>

    <div class="mt-7 grid gap-6 lg:grid-cols-[360px_1fr]">
        <form class="card grid h-fit gap-4" method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            <div>
                <p class="text-xs font-black tracking-widest text-brand">NEW COUPON</p>
                <h2 class="mt-2 text-xl font-black">เพิ่มคูปอง</h2>
            </div>

            @if($errors->any())
                <div class="rounded-xl border border-brand/30 bg-brand/10 px-4 py-3 text-sm font-bold text-brand">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="label">รหัสคูปอง</label>
                <input name="code" value="{{ old('code') }}" placeholder="เช่น POINT50" required>
            </div>
            <div>
                <label class="label">ชื่อคูปอง</label>
                <input name="name" value="{{ old('name') }}" placeholder="เช่น ส่วนลด 50 บาท" required>
            </div>
            <div>
                <label class="label">รายละเอียด</label>
                <textarea name="description" placeholder="เงื่อนไขการใช้คูปอง">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">ประเภทส่วนลด</label>
                    <select name="discount_type">
                        <option value="fixed" @selected(old('discount_type', 'fixed') === 'fixed')>ส่วนลดบาท</option>
                        <option value="percentage" @selected(old('discount_type') === 'percentage')>ส่วนลด %</option>
                    </select>
                </div>
                <div>
                    <label class="label">มูลค่าส่วนลด</label>
                    <input name="discount_value" type="number" min="0.01" step="0.01" value="{{ old('discount_value') }}" placeholder="50" required>
                </div>
            </div>
            <div>
                <label class="label">คะแนนที่ใช้แลก <span class="font-normal text-black/45 dark:text-white/45">(ไม่บังคับ)</span></label>
                <input name="required_points" type="number" min="1" value="{{ old('required_points') }}" placeholder="เช่น 100">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">เริ่มใช้</label>
                    <input name="valid_from" type="date" value="{{ old('valid_from', today()->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label class="label">สิ้นสุด</label>
                    <input name="valid_until" type="date" value="{{ old('valid_until', today()->addMonth()->format('Y-m-d')) }}" required>
                </div>
            </div>
            <label class="inline-flex items-center gap-3 text-sm font-bold">
                <input class="toggle" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                <span>เปิดใช้งานทันที</span>
            </label>
            <button class="btn btn-primary">เพิ่มคูปอง</button>
        </form>

        <div class="grid gap-3">
            @forelse($coupons as $coupon)
                <details class="card">
                    <summary class="cursor-pointer font-bold">
                        {{ $coupon->code }} · {{ $coupon->name }}
                        <span class="ml-2 text-sm text-brand">{{ $coupon->required_points ? number_format($coupon->required_points).' คะแนน' : 'ไม่ใช้คะแนน' }}</span>
                    </summary>
                    <form class="mt-5 grid gap-3 md:grid-cols-2" method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
                        @csrf
                        @method('PUT')
                        <input name="code" value="{{ $coupon->code }}" required>
                        <input name="name" value="{{ $coupon->name }}" required>
                        <textarea class="md:col-span-2" name="description" placeholder="รายละเอียด">{{ $coupon->description }}</textarea>
                        <select name="discount_type">
                            <option value="fixed" @selected($coupon->discount_type === 'fixed')>ส่วนลดบาท</option>
                            <option value="percentage" @selected($coupon->discount_type === 'percentage')>ส่วนลด %</option>
                        </select>
                        <input name="discount_value" type="number" min="0.01" step="0.01" value="{{ $coupon->discount_value }}" required>
                        <input name="required_points" type="number" min="1" value="{{ $coupon->required_points }}" placeholder="คะแนนที่ต้องใช้">
                        <div class="grid grid-cols-2 gap-3">
                            <input name="valid_from" type="date" value="{{ $coupon->valid_from->format('Y-m-d') }}" required>
                            <input name="valid_until" type="date" value="{{ $coupon->valid_until->format('Y-m-d') }}" required>
                        </div>
                        <label class="inline-flex items-center gap-3 text-sm font-bold">
                            <input class="toggle" name="is_active" type="checkbox" value="1" @checked($coupon->is_active)>
                            <span>เปิดใช้งาน</span>
                        </label>
                        <button class="btn btn-dark">บันทึก</button>
                    </form>
                    <form class="mt-3" method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('ยืนยันการลบคูปอง?')">
                        @csrf
                        @method('DELETE')
                        <button class="text-sm font-bold text-brand underline">ลบคูปอง</button>
                    </form>
                </details>
            @empty
                <div class="card py-12 text-center text-black/45 dark:text-white/45">ยังไม่มีคูปอง</div>
            @endforelse
        </div>
    </div>
</section>

<section class="shell pb-12">
    <div class="card overflow-x-auto">
        <h2 class="font-black">รอยืนยันใช้คูปอง</h2>
        <p class="mt-1 text-sm text-black/50 dark:text-white/50">พนักงานตรวจสอบและกดยืนยันเมื่อใช้คูปองที่ร้านแล้ว</p>
        <table class="mt-4 min-w-[600px] w-full text-left text-sm">
            <thead class="border-b border-black/10 text-xs text-black/50 dark:border-white/10 dark:text-white/50">
                <tr><th class="p-3">สมาชิก</th><th class="p-3">คูปอง</th><th class="p-3">แลกเมื่อ</th><th class="p-3"></th></tr>
            </thead>
            <tbody>
                @forelse($pendingUsages as $usage)
                    <tr class="border-b border-black/5 dark:border-white/10">
                        <td class="p-3">{{ $usage->member->first_name }} {{ $usage->member->last_name }}</td>
                        <td class="p-3">{{ $usage->coupon->code }} · {{ $usage->coupon->name }}</td>
                        <td class="p-3">{{ $usage->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.coupon-usages.confirm', $usage) }}">@csrf<button class="btn btn-primary px-3 py-2">ยืนยันการใช้</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-black/45 dark:text-white/45">ไม่มีคูปองรอยืนยัน</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="shell pb-12">
    <div class="card overflow-x-auto">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-black tracking-widest text-brand">COUPON REDEMPTION</p>
                <h2 class="mt-2 text-xl font-black">ผู้ใช้ที่แลกรับคูปองแล้ว</h2>
            </div>
            <span class="rounded-full bg-black/5 px-3 py-1 text-sm font-bold dark:bg-white/10">{{ $redeemedUsages->count() }} รายการ</span>
        </div>
        <table class="min-w-[650px] w-full text-left text-sm">
            <thead class="border-b border-black/10 text-xs uppercase tracking-[.12em] text-black/50 dark:border-white/10 dark:text-white/50">
                <tr><th class="px-3 py-3">สมาชิก</th><th class="px-3 py-3">คูปอง</th><th class="px-3 py-3">วันที่แลก</th><th class="px-3 py-3">สถานะ</th></tr>
            </thead>
            <tbody>
                @forelse($redeemedUsages as $usage)
                    <tr class="border-b border-black/5 last:border-0 dark:border-white/10">
                        <td class="px-3 py-4 font-bold">{{ $usage->member->first_name }} {{ $usage->member->last_name }}</td>
                        <td class="px-3 py-4">{{ $usage->coupon->code }} · {{ $usage->coupon->name }}</td>
                        <td class="px-3 py-4">{{ $usage->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-4 font-bold {{ $usage->used_at ? 'text-emerald-600 dark:text-emerald-400' : 'text-brand' }}">
                            {{ $usage->used_at ? 'ยืนยันใช้แล้ว' : 'รอยืนยันใช้' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-8 text-center text-black/45 dark:text-white/45">ยังไม่มีผู้ใช้แลกรับคูปอง</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
