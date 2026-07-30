@extends('layouts.app')

@section('content')
<section class="shell member-page py-12">
    <p class="text-xs font-black tracking-widest text-brand">MEMBER AREA</p>
    <h1 class="mt-2 text-4xl font-black">สวัสดี : {{ $member->first_name }}</h1>
        <p class="mt-2 text-sm text-black/55 dark:text-white/55">เลือกการจองที่ใช้และรับคะแนนสะสมได้แล้ววันนี้ !</p>

    @if($promotions->isNotEmpty())
        <section class="mt-7" data-promotion-carousel>
            <div class="mb-3 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-black tracking-[.2em] text-brand">SPECIAL FOR YOU</p>
                    <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <h2 class="text-xl font-black">โปรโมชั่น</h2>
                        <span class="rounded-full bg-brand px-3 py-1 text-[10px] font-black tracking-[.18em] text-white">SM HAIR DESIGN · PROMOTION</span>
                    </div>
                </div>
                @if($promotions->count() > 1)
                    <span class="min-w-10 text-center text-xs font-black tabular-nums text-black/45 dark:text-white/45" data-promotion-counter>1 / {{ $promotions->count() }}</span>
                @endif
            </div>

            <div class="relative">
                @if($promotions->count() > 1)
                    <button class="absolute left-2 top-1/2 z-20 grid size-10 -translate-y-1/2 place-items-center rounded-full border border-white/25 bg-black/45 p-0 text-white shadow-lg shadow-black/20 backdrop-blur-sm transition hover:scale-110 hover:bg-black/65 focus-visible:ring-4 focus-visible:ring-white/40 sm:left-3 sm:size-12" type="button" aria-label="โปรโมชั่นก่อนหน้า" data-promotion-prev>
                        <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 18 9 12l6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button class="absolute right-2 top-1/2 z-20 grid size-10 -translate-y-1/2 place-items-center rounded-full border border-white/25 bg-black/45 p-0 text-white shadow-lg shadow-black/20 backdrop-blur-sm transition hover:scale-110 hover:bg-black/65 focus-visible:ring-4 focus-visible:ring-white/40 sm:right-3 sm:size-12" type="button" aria-label="โปรโมชั่นถัดไป" data-promotion-next>
                        <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                @endif

                <div class="{{ $promotions->count() === 1 ? '' : 'overflow-hidden pb-3' }}" data-promotion-viewport>
                    <div class="{{ $promotions->count() === 1 ? 'grid' : 'flex gap-4' }}" data-promotion-track>
                        @foreach($promotions as $promotion)
                            <article
                                class="group relative isolate min-h-56 overflow-hidden rounded-3xl border border-black/10 bg-black bg-cover bg-center bg-no-repeat p-6 text-white shadow-xl shadow-black/10 dark:border-white/10 dark:shadow-black/30 sm:min-h-64 sm:p-8 {{ $promotions->count() === 1 ? 'w-full' : 'min-w-[88%] sm:min-w-[620px] lg:min-w-[760px]' }}"
                                @if($promotion->image_path)
                                    style="background-image: url('{{ route('promotions.image', $promotion) }}?v={{ $promotion->updated_at->timestamp }}')"
                                @endif
                                aria-label="{{ $promotion->name }}"
                                data-promotion-slide
                            >
                            @unless($promotion->image_path)
                                <div class="pointer-events-none absolute inset-0 z-0 bg-gradient-to-br from-neutral-950 via-black to-red-950"></div>
                            @endunless
                            <div class="relative z-10 flex h-full max-w-xl flex-col justify-between">
                                <div>
                                    @if($promotion->description)
                                        <p class="max-w-lg text-sm leading-relaxed text-white/70 sm:text-base">{{ $promotion->description }}</p>
                                    @endif
                                </div>

                                <div class="mt-7 flex flex-wrap items-center gap-4">
                                    @if($promotion->button_text && $promotion->button_url)
                                        <a class="btn bg-white px-5 py-3 text-black transition hover:-translate-y-0.5 hover:bg-red-100" href="{{ $promotion->button_url }}" target="_blank" rel="noopener noreferrer">{{ $promotion->button_text }} →</a>
                                    @endif
                                    <span class="text-xs font-bold text-white/50">ถึง {{ $promotion->ends_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

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
                        <p class="text-xs font-black text-white">{{ $upcoming->status === 'pending' ? 'AWAITING PAYMENT' : 'CONFIRMED' }}</p>
                    </div>
                </div>

                <div class="ticket-divider"></div>

                <div class="grid gap-2 text-sm">
                    <div class="flex items-center justify-between gap-3"><span class="text-red-300/70">บริการ</span><span class="text-right font-bold text-white">{{ $upcoming->service->name }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-red-300/70">สาขา</span><span class="text-right font-bold text-white">{{ $upcoming->branch->name }}</span></div>
                    <div class="flex items-center justify-between gap-3"><span class="text-red-300/70">ช่าง</span><span class="text-right font-bold text-white">{{ $upcoming->employee->name }}</span></div>
                </div>
                @if($upcoming->status === 'pending' && $upcoming->payment)
                    <a class="mt-5 flex w-full items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-black text-black transition hover:bg-red-100" href="{{ route('payments.show', $upcoming) }}">
                        ชำระเงิน ฿{{ number_format((float) $upcoming->payment->amount, 2) }}
                    </a>
                @endif
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
