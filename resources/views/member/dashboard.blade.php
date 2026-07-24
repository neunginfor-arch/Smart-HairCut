@extends('layouts.app')

@section('content')
<section class="shell member-page py-12">
    <p class="text-xs font-black tracking-widest text-brand">MEMBER AREA</p>
    <h1 class="mt-2 text-4xl font-black">สวัสดี : {{ $member->first_name }}</h1>
        <p class="mt-2 text-sm text-black/55 dark:text-white/55">เลือกการจองที่ใช้และรับคะแนนสะสมได้แล้ววันนี้ !</p>
    <div class="mt-7 grid grid-cols-2 gap-4 lg:grid-cols-3">
        <div class="card min-w-0 p-4 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-bold text-black/50 dark:text-white/50">คะแนนสะสม</p>
                <i class="bxf bx-medal -mt-1 text-4xl leading-none text-brand sm:text-5xl" aria-hidden="true"></i>
            </div>
            <p class="mt-3 text-3xl font-black">{{ number_format($points) }}</p>
            <div class="mt-4 border-t border-black/10 pt-4 dark:border-white/10">
                <a class="btn btn-primary w-full px-3 py-2 text-xs sm:w-auto sm:px-4 sm:text-sm" href="{{ route('points.index') }}">ดูประวัติคะแนน</a>
            </div>
        </div>

        <div class="card min-w-0 p-4 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-bold text-black/50 dark:text-white/50">คูปองที่สามารถใช้ได้</p>
                <i class="bxf bx-discount -mt-1 text-4xl leading-none text-brand sm:text-5xl" aria-hidden="true"></i>
            </div>
            <p class="mt-3 text-3xl font-black">{{ number_format($couponCount) }}</p>
            <div class="mt-4 border-t border-black/10 pt-4 dark:border-white/10">
                <a class="btn btn-primary w-full px-3 py-2 text-xs sm:w-auto sm:px-4 sm:text-sm" href="{{ route('coupons.index') }}">ดูคูปอง</a>
            </div>
        </div>

        <article class="next-booking-ticket col-span-2 min-w-0 lg:col-span-1">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black tracking-[.2em] text-red-300">NEXT APPOINTMENT</p>
                    <h2 class="mt-1 text-lg font-black text-white">คิวถัดไป</h2>
                </div>
                @if($upcoming)
                    <span class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold tracking-wide text-red-100">{{ $upcoming->booking_no }}</span>
                @endif
            </div>

            <div class="ticket-divider"></div>

            @if($upcoming)
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-red-200">{{ $upcoming->booking_date->format('d/m/Y') }}</p>
                        <p class="mt-1 text-3xl font-black tracking-tight text-white">{{ substr($upcoming->start_time, 0, 5) }} <span class="text-sm font-bold text-red-200">น.</span></p>
                    </div>
                    <div class="rounded-xl bg-brand px-3 py-2 text-right shadow-lg shadow-red-950/30">
                        <p class="text-[10px] font-black tracking-wider text-red-100">STATUS</p>
                        <p class="text-xs font-black text-white">CONFIRMED</p>
                    </div>
                </div>

                <div class="ticket-divider"></div>

                <div class="grid gap-2 text-sm">
                    <div class="flex items-center justify-between gap-3"><span class="text-red-300/70">บริการ</span><span class="text-right font-bold text-white">{{ $upcoming->service->name }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-red-300/70">สาขา</span><span class="text-right font-bold text-white">{{ $upcoming->branch->name }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-red-300/70">ช่าง</span><span class="text-right font-bold text-white">{{ $upcoming->employee->name }}</span></div>
                </div>
            @else
                <div class="py-5 text-center">
                    <i class="bxf bx-calendar-x text-4xl text-red-300/70" aria-hidden="true"></i>
                    <p class="mt-3 text-sm text-red-100">ยังไม่มีคิวที่กำลังจะมาถึง</p>
                </div>
            @endif
        </article>
    </div>

    <section class="card mt-6 flex flex-wrap items-center justify-between gap-5">
        <div>
            <p class="text-xs font-black tracking-widest text-brand">LINE NOTIFICATIONS</p>
            <h2 class="mt-2 text-xl font-black">รับการแจ้งเตือนผ่าน LINE <i class="bxf bx-bell-ring boxicon-bell ml-2 text-lg leading-none" aria-hidden="true"></i></h2>
            <p class="mt-2 text-sm text-black/55 dark:text-white/55">กดเพิ่มเพื่อน แล้วส่งเบอร์โทรศัพท์ที่ใช้สมัครสมาชิกในแชต LINE เพื่อเชื่อมการแจ้งเตือน</p>
            @if($member->line_user_id)
                <p class="mt-2 text-sm font-bold ">เชื่อม LINE สำหรับรับการแจ้งเตือนแล้ว <i class="bxf bx-message-circle-check"></i></p>
            @endif
        </div>

        @if(config('line.add_friend_url'))
            <a class="btn btn-primary" href="{{ config('line.add_friend_url') }}" target="_blank" rel="noopener">รับการแจ้งเตือนผ่าน LINE <i class="bxf bx-bell-ring boxicon-bell ml-2 text-lg leading-none" aria-hidden="true"></i></a>
        @else
            <span class="btn cursor-not-allowed bg-neutral-300 text-neutral-500">กำลังตั้งค่า LINE</span>
        @endif
    </section>

    @include('member.booking-widget')
</section>
@endsection
