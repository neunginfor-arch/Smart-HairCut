@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<section class="shell py-12">
    <div class="rounded-3xl bg-ink p-7 text-white sm:p-10">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div>
                <p class="text-xs font-black tracking-[.25em] text-red-400">SM HAIR DESIGN · ADMIN</p>
                <h1 class="mt-3 text-4xl font-black sm:text-5xl">Dashboard</h1>
                <p class="mt-3 text-sm text-white/60">สวัสดี {{ $admin->name }} · {{ $admin->role->display_name }}</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn border border-white/20 text-white">ออกจากระบบ</button>
            </form>
        </div>

        <div class="mt-8 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-white/10 p-4">
                <p class="text-xs text-white/50">ยอดขายเดือนนี้</p>
                <p class="mt-2 text-2xl font-black">฿{{ number_format($stats['sales_month'], 2) }}</p>
            </div>
            <div class="rounded-2xl bg-white/10 p-4">
                <p class="text-xs text-white/50">ยอดขายปีนี้</p>
                <p class="mt-2 text-2xl font-black">฿{{ number_format($stats['sales_year'], 2) }}</p>
            </div>
            <div class="rounded-2xl bg-brand p-4">
                <p class="text-xs text-white/80">มูลค่าส่วนลดที่แลกเดือนนี้</p>
                <p class="mt-2 text-2xl font-black">฿{{ number_format($stats['redeemed_discount_month'], 2) }}</p>
            </div>
        </div>
    </div>

    <div class="mt-7 grid gap-4 sm:grid-cols-3">
        <div class="card">
            <p class="text-xs font-bold text-black/50 dark:text-white/50">สมาชิกใหม่วันนี้</p>
            <p class="mt-3 text-3xl font-black">{{ number_format($stats['members_today']) }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-bold text-black/50 dark:text-white/50">รายการจองวันนี้</p>
            <p class="mt-3 text-3xl font-black">{{ number_format($stats['bookings_today']) }}</p>
        </div>
        <div class="card">
            <p class="text-xs font-bold text-black/50 dark:text-white/50">ยอดขายวันนี้</p>
            <p class="mt-3 text-3xl font-black text-brand">฿{{ number_format($stats['sales_today'], 2) }}</p>
        </div>
    </div>

    <div class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div class="card flex items-center justify-between gap-4"><p class="font-bold">จัดการสมาชิก</p><a class="btn btn-primary px-4 py-2" href="{{ route('admin.members') }}">คลิ๊ก</a></div>
        <div class="card flex items-center justify-between gap-4"><p class="font-bold">จัดการคิว</p><a class="btn btn-primary px-4 py-2" href="{{ route('admin.bookings') }}">คลิ๊ก</a></div>
        <div class="card flex items-center justify-between gap-4"><p class="font-bold">จัดการหน้าจองคิว</p><a class="btn btn-primary px-4 py-2" href="{{ route('admin.booking-setup') }}">คลิ๊ก</a></div>
        <div class="card flex items-center justify-between gap-4"><p class="font-bold">จัดการคูปองและคะแนนแลก</p><a class="btn btn-primary px-4 py-2" href="{{ route('admin.coupons') }}">คลิ๊ก</a></div>
        <div class="card flex items-center justify-between gap-4"><p class="font-bold">จัดการโปรโมชั่น</p><a class="btn btn-primary px-4 py-2" href="{{ route('admin.promotions') }}">คลิ๊ก</a></div>
        <div class="card flex items-center justify-between gap-4"><p class="font-bold">จัดการหน้าการแจ้งเตือน</p><a class="btn btn-primary px-4 py-2" href="{{ route('admin.section', 'notifications') }}">คลิ๊ก</a></div>
        <div class="card flex items-center justify-between gap-4"><p class="font-bold">จัดการผู้ดูแลระบบ</p><a class="btn btn-primary px-4 py-2" href="{{ route('admin.users') }}">คลิ๊ก</a></div>
    </div>
</section>
@endsection
