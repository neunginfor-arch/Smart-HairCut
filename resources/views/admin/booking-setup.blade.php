@extends('layouts.app')
@section('content')
<section class="shell py-12"><div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-black tracking-widest text-brand">ADMIN CONSOLE</p><h1 class="mt-2 text-4xl font-black">จัดการหน้าจองคิว</h1><p class="mt-2 text-sm text-black/55 dark:text-white/55">สาขา ช่าง และบริการที่แสดงให้ลูกค้าเลือก</p></div><a class="btn border border-black/15 dark:border-white/20" href="{{ route('admin.dashboard') }}">← Admin Dashboard</a></div><div class="mt-7 grid gap-6 xl:grid-cols-3">@foreach(['branch'=>'สาขา','employee'=>'ช่าง','service'=>'บริการ'] as $type=>$title)<section class="card"><h2 class="text-xl font-black">{{ $title }}</h2>@if($type==='branch')<form class="mt-5 grid gap-3" method="POST" action="{{ route('admin.branches.store') }}">@csrf<input name="name" placeholder="ชื่อสาขา" required><input name="address" placeholder="ที่อยู่"><input name="phone" placeholder="เบอร์โทร">@elseif($type==='employee')<form class="mt-5 grid gap-3" method="POST" action="{{ route('admin.employees.store') }}">@csrf<select name="branch_id">@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select><input name="name" placeholder="ชื่อช่าง" required><input name="phone" placeholder="เบอร์โทร"><input name="line_user_id" placeholder="LINE User ID">@else<form class="mt-5 grid gap-3" method="POST" action="{{ route('admin.services.store') }}">@csrf<input name="name" placeholder="ชื่อบริการ" required><div class="grid grid-cols-2 gap-3"><input name="duration_minutes" type="number" min="30" placeholder="นาที" required><input name="price" type="number" min="0" step="0.01" placeholder="ราคา" required></div>@endif<label class="inline-flex items-center gap-3 text-sm font-bold"><input class="toggle" name="is_active" type="checkbox" value="1" checked><span>เปิดใช้งาน</span></label><button class="btn btn-primary">+ เพิ่ม{{ $title }}</button></form><div class="mt-6 space-y-3">@php($items=$type==='branch'?$branches:($type==='employee'?$employees:$services))@forelse($items as $item)<details class="rounded-xl border border-black/10 p-3 dark:border-white/10"><summary class="cursor-pointer font-bold">{{ $item->name }} <span class="ml-2 text-xs {{ $item->is_active?'text-emerald-600':'text-black/40' }}">{{ $item->is_active?'เปิดใช้':'ปิดใช้' }}</span></summary>@if($type==='branch')<form class="mt-4 grid gap-2" method="POST" action="{{ route('admin.branches.update',$item) }}">@csrf @method('PUT')<input name="name" value="{{ $item->name }}"><input name="address" value="{{ $item->address }}" placeholder="ที่อยู่"><input name="phone" value="{{ $item->phone }}" placeholder="เบอร์โทร">@elseif($type==='employee')<form class="mt-4 grid gap-2" method="POST" action="{{ route('admin.employees.update',$item) }}">@csrf @method('PUT')<select name="branch_id">@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($item->branch_id===$branch->id)>{{ $branch->name }}</option>@endforeach</select><input name="name" value="{{ $item->name }}"><input name="phone" value="{{ $item->phone }}"><input name="line_user_id" value="{{ $item->line_user_id }}">@else<form class="mt-4 grid gap-2" method="POST" action="{{ route('admin.services.update',$item) }}">@csrf @method('PUT')<input name="name" value="{{ $item->name }}"><div class="grid grid-cols-2 gap-2"><input name="duration_minutes" type="number" value="{{ $item->duration_minutes }}"><input name="price" type="number" step="0.01" value="{{ $item->price }}"></div>@endif<label class="inline-flex items-center gap-3 text-sm"><input class="toggle" name="is_active" type="checkbox" value="1" @checked($item->is_active)><span>เปิดใช้งาน</span></label><button class="btn btn-dark px-3 py-2">บันทึก</button></form><form class="mt-2" method="POST" action="{{ route('admin.'.($type==='branch'?'branches':($type==='employee'?'employees':'services')).'.destroy',$item) }}" onsubmit="return confirm('ยืนยันการลบ?')">@csrf @method('DELETE')<button class="text-xs font-bold text-brand underline">ลบรายการนี้</button></form></details>@empty<p class="py-5 text-sm text-black/45">ยังไม่มี{{ $title }}</p>@endforelse</div></section>@endforeach</div></section>
<section class="shell pb-12">
    <div class="card">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-black tracking-widest text-brand">SHOP CLOSURE</p>
                <h2 class="mt-2 text-2xl font-black">จัดการวันหยุดร้าน</h2>
                <p class="mt-2 text-sm text-black/55 dark:text-white/55">กำหนดช่วงวันที่ไม่เปิดรับจอง ระบบจะปิดทุกช่วงเวลาให้ลูกค้าโดยอัตโนมัติ</p>
            </div>
        </div>

        <form class="mt-6 grid gap-4 md:grid-cols-[1fr_1fr_1.5fr_auto]" method="POST" action="{{ route('admin.shop-closures.store') }}">
            @csrf
            <div><label class="label">วันเริ่มหยุด</label><input name="start_date" type="date" min="{{ today()->format('Y-m-d') }}" required></div>
            <div><label class="label">วันสิ้นสุดวันหยุด</label><input name="end_date" type="date" min="{{ today()->format('Y-m-d') }}" required></div>
            <div><label class="label">หมายเหตุ (ไม่บังคับ)</label><input name="note" maxlength="255" placeholder="เช่น ปิดปรับปรุงร้าน"></div>
            <button class="btn btn-primary self-end">บันทึกวันหยุด</button>
        </form>

        <div class="mt-7 overflow-x-auto border-t border-black/10 pt-6 dark:border-white/10">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-black/10 text-xs uppercase tracking-[.12em] text-black/50 dark:border-white/10 dark:text-white/50">
                        <th class="px-3 py-3">เริ่มหยุด</th>
                        <th class="px-3 py-3">สิ้นสุด</th>
                        <th class="px-3 py-3">หมายเหตุ</th>
                        <th class="px-3 py-3">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($closures as $closure)
                        <tr class="border-b border-black/5 last:border-0 dark:border-white/10">
                            <td class="px-3 py-4 font-bold">{{ $closure->start_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-4">{{ $closure->end_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-4">{{ $closure->note ?: '—' }}</td>
                            <td class="px-3 py-4">
                                <details>
                                    <summary class="cursor-pointer font-bold text-brand">แก้ไข</summary>
                                    <form class="mt-3 grid min-w-64 gap-2" method="POST" action="{{ route('admin.shop-closures.update', $closure) }}">
                                        @csrf
                                        @method('PUT')
                                        <input name="start_date" type="date" value="{{ $closure->start_date->format('Y-m-d') }}" required>
                                        <input name="end_date" type="date" value="{{ $closure->end_date->format('Y-m-d') }}" required>
                                        <input name="note" value="{{ $closure->note }}" maxlength="255" placeholder="หมายเหตุ">
                                        <button class="btn btn-dark px-3 py-2">บันทึก</button>
                                    </form>
                                </details>
                                <form class="mt-2" method="POST" action="{{ route('admin.shop-closures.destroy', $closure) }}" onsubmit="return confirm('ยืนยันการลบวันหยุดนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="font-bold text-brand underline">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8 text-center text-black/45 dark:text-white/45">ยังไม่ได้กำหนดวันหยุดร้าน</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="shell pb-12">
    <div class="card">
        <div>
            <p class="text-xs font-black tracking-widest text-brand">EMPLOYEE TIME OFF</p>
            <h2 class="mt-2 text-2xl font-black">จัดการวันหยุดพนักงาน</h2>
            <p class="mt-2 text-sm text-black/55 dark:text-white/55">กำหนดช่วงวันและเวลาที่ช่างลา ระบบจะไม่แสดงช่างเมื่อหยุดเต็มวัน และปิดเฉพาะเวลาที่ลาเมื่อเป็นการลาบางช่วงเวลา</p>
        </div>

        @if($errors->hasAny(['employee_id', 'start_date', 'start_time', 'end_date', 'end_time']))
            <div class="mt-5 rounded-xl border border-brand/30 bg-brand/10 px-4 py-3 text-sm font-bold text-brand">
                {{ $errors->first('employee_id') ?: $errors->first('start_date') ?: $errors->first('start_time') ?: $errors->first('end_date') ?: $errors->first('end_time') }}
            </div>
        @endif

        <form class="mt-6 grid gap-4 xl:grid-cols-[1.1fr_1fr_.75fr_1fr_.75fr_1.3fr_auto]" method="POST" action="{{ route('admin.employee-time-offs.store') }}">
            @csrf
            <div>
                <label class="label">พนักงาน / ช่าง</label>
                <select name="employee_id" required>
                    @foreach($employees->where('is_active', true) as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }} — {{ $employee->branch?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="label">วันเริ่มหยุด</label><input name="start_date" type="date" value="{{ old('start_date', today()->format('Y-m-d')) }}" min="{{ today()->format('Y-m-d') }}" required></div>
            <div><label class="label">เวลาเริ่ม</label><input name="start_time" type="time" value="{{ old('start_time', '10:00') }}" required></div>
            <div><label class="label">วันสิ้นสุด</label><input name="end_date" type="date" value="{{ old('end_date', today()->format('Y-m-d')) }}" min="{{ today()->format('Y-m-d') }}" required></div>
            <div><label class="label">เวลาสิ้นสุด</label><input name="end_time" type="time" value="{{ old('end_time', '19:00') }}" required></div>
            <div><label class="label">หมายเหตุ (ไม่บังคับ)</label><input name="note" maxlength="255" placeholder="เช่น ลาพักร้อน / ลากิจ"></div>
            <button class="btn btn-primary self-end">บันทึกวันหยุด</button>
        </form>

        <div class="mt-7 overflow-x-auto border-t border-black/10 pt-6 dark:border-white/10">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-black/10 text-xs uppercase tracking-[.12em] text-black/50 dark:border-white/10 dark:text-white/50">
                        <th class="px-3 py-3">ช่าง</th>
                        <th class="px-3 py-3">เริ่มหยุด</th>
                        <th class="px-3 py-3">สิ้นสุด</th>
                        <th class="px-3 py-3">หมายเหตุ</th>
                        <th class="px-3 py-3">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timeOffs as $timeOff)
                        <tr class="border-b border-black/5 last:border-0 dark:border-white/10">
                            <td class="px-3 py-4 font-bold">{{ $timeOff->employee->name }}<span class="mt-1 block text-xs font-normal text-black/45 dark:text-white/45">{{ $timeOff->employee->branch?->name }}</span></td>
                            <td class="px-3 py-4">{{ $timeOff->start_at->format('d/m/Y H:i') }} น.</td>
                            <td class="px-3 py-4">{{ $timeOff->end_at->format('d/m/Y H:i') }} น.</td>
                            <td class="px-3 py-4">{{ $timeOff->note ?: '—' }}</td>
                            <td class="px-3 py-4">
                                <details>
                                    <summary class="cursor-pointer font-bold text-brand">แก้ไข</summary>
                                    <form class="mt-3 grid min-w-72 gap-2" method="POST" action="{{ route('admin.employee-time-offs.update', $timeOff) }}">
                                        @csrf
                                        @method('PUT')
                                        <select name="employee_id">
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}" @selected($timeOff->employee_id === $employee->id)>{{ $employee->name }} — {{ $employee->branch?->name }}</option>
                                            @endforeach
                                        </select>
                                        <input name="start_date" type="date" value="{{ $timeOff->start_at->format('Y-m-d') }}" required>
                                        <input name="start_time" type="time" value="{{ $timeOff->start_at->format('H:i') }}" required>
                                        <input name="end_date" type="date" value="{{ $timeOff->end_at->format('Y-m-d') }}" required>
                                        <input name="end_time" type="time" value="{{ $timeOff->end_at->format('H:i') }}" required>
                                        <input name="note" value="{{ $timeOff->note }}" maxlength="255" placeholder="หมายเหตุ">
                                        <button class="btn btn-dark px-3 py-2">บันทึก</button>
                                    </form>
                                </details>
                                <form class="mt-2" method="POST" action="{{ route('admin.employee-time-offs.destroy', $timeOff) }}" onsubmit="return confirm('ยืนยันการลบวันหยุดพนักงานนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="font-bold text-brand underline">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-8 text-center text-black/45 dark:text-white/45">ยังไม่ได้กำหนดวันหยุดพนักงาน</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
