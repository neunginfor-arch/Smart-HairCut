@extends('layouts.app')

@section('content')
<section class="shell member-page py-12">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-black tracking-widest text-brand">MEMBER AREA</p>
            <h1 class="mt-2 text-4xl font-black">คูปองของฉัน</h1>
            <p class="mt-2 text-sm text-black/55 dark:text-white/55">คะแนนสะสมปัจจุบัน: <b class="font-bold text-xl">{{ number_format($points) }}</b> คะแนน</p>
        </div>
        <a class="btn border border-black/15 dark:border-white/20" href="{{ route('member.dashboard') }}">← Member Dashboard</a>
    </div>

    <div class="mt-7 grid gap-4 md:grid-cols-2">
        @forelse($coupons as $coupon)
            <article class="card border-l-4 border-l-brand">
                <p class="text-xs font-bold text-brand">{{ $coupon->code }}</p>
                <h2 class="mt-2 text-xl font-black">{{ $coupon->name }}</h2>
                <p class="mt-2 text-sm text-black/55 dark:text-white/55">{{ $coupon->description ?: 'ใช้สิทธิ์โดยพนักงานยืนยันที่ร้าน' }}</p>
                <div class="mt-5 flex items-end justify-between">
                    <p class="text-2xl font-black">{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value.'%' : '฿'.number_format($coupon->discount_value, 2) }}</p>
                    <p class="text-right text-xs text-black/50 dark:text-white/50">วันที่ใช้ {{ $coupon->valid_from->format('d/m/Y') }}<br>สิ้นสุด {{ $coupon->valid_until->format('d/m/Y') }}</p>
                </div>
                @if($coupon->required_points)
                    <div class="mt-5 border-t border-black/10 pt-4 dark:border-white/10">
                        @if(in_array($coupon->id, $redeemed))
                            <p class="text-sm font-bold text-emerald-600">แลกแล้ว · รอพนักงานยืนยัน</p>
                        @else
                            <form method="POST" action="{{ route('coupons.redeem', $coupon) }}">
                                @csrf
                                <button class="btn {{ $points >= $coupon->required_points ? 'btn-primary' : 'bg-neutral-200 text-neutral-500' }} w-full" {{ $points < $coupon->required_points ? 'disabled' : '' }}>แลก {{ number_format($coupon->required_points) }} คะแนน</button>
                            </form>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="card py-12 text-center text-black/50 dark:text-white/50 md:col-span-2">ขณะนี้ยังไม่มีคูปองที่ใช้ได้</div>
        @endforelse
    </div>
</section>
@endsection
