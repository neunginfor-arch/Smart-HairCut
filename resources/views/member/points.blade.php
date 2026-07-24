@extends('layouts.app')

@section('content')
<section class="shell member-page py-12">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-black tracking-widest text-brand">MEMBER AREA</p>
            <h1 class="mt-2 text-4xl font-black">คะแนนสะสม</h1>
        </div>
        <a class="btn border border-black/15 dark:border-white/20" href="{{ route('member.dashboard') }}">← Member Dashboard</a>
    </div>

    <div class="card mt-7 bg-ink text-white">
        <p class="text-xs font-bold tracking-widest text-red-400">CURRENT POINTS</p>
        <p class="mt-3 text-5xl font-black">{{ number_format($points) }}</p>
        <p class="mt-2 text-sm text-white/60">ทุกยอดขาย 100 บาท รับ 10 คะแนน โดยพนักงานเป็นผู้บันทึกหลังชำระเงิน</p>
    </div>

    <div class="card mt-7 overflow-x-auto">
        <h2 class="mb-4 font-black">ประวัติคะแนน</h2>
        <table class="w-full min-w-[620px] text-left text-sm">
            <thead class="border-b border-black/10 text-xs text-black/50 dark:border-white/50">
                <tr><th class="p-3">วันที่</th><th class="p-3">รายการ</th><th class="p-3">Booking No.</th><th class="p-3">คะแนน</th></tr>
            </thead>
            <tbody>
                @forelse($histories as $history)
                    <tr class="border-b border-black/5 dark:border-white/5">
                        <td class="p-3">{{ $history->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-3">{{ $history->description ?: $history->type }}</td>
                        <td class="p-3">{{ $history->booking?->booking_no ?: '—' }}</td>
                        <td class="p-3 font-bold {{ $history->points >= 0 ? 'text-emerald-600' : 'text-brand' }}">{{ $history->points >= 0 ? '+' : '' }}{{ $history->points }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center text-black/45">ยังไม่มีประวัติคะแนน</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-5">{{ $histories->links() }}</div>
    </div>
</section>
@endsection
