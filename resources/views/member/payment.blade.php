@extends('layouts.app')

@section('content')
<section class="shell member-page py-10">
    <div class="mx-auto max-w-4xl">
        <div class="rounded-3xl bg-ink p-7 text-white sm:p-9">
            <p class="text-xs font-black tracking-[.2em] text-red-400">PAYMENT</p>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black sm:text-4xl">ชำระเงินเพื่อยืนยันการจอง</h1>
                    <p class="mt-2 text-sm text-white/60">Booking No. {{ $booking->booking_no }}</p>
                </div>
                <p class="text-3xl font-black text-red-400">฿{{ number_format((float) $payment->amount, 2) }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[.9fr_1.1fr]">
            <div class="card">
                <p class="text-xs font-black tracking-widest text-brand">SCAN TO PAY</p>
                @if($hasPaymentQr)
                    <div class="mt-5 overflow-hidden rounded-2xl border border-black/10 bg-white p-4">
                        <img class="mx-auto max-h-80 w-full object-contain" src="{{ route('payments.qr-image') }}?v={{ now()->timestamp }}" alt="QR Code สำหรับชำระเงิน">
                        <a class="btn btn-dark mt-4 flex w-full items-center justify-center gap-2" href="{{ route('payments.qr-image.download') }}">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3v11m0 0 4-4m-4 4-4-4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            บันทึกรูป QR CODE
                        </a>
                    </div>
                @else
                    <div class="mt-5 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-sm font-bold text-amber-900">
                        ร้านค้ายังไม่ได้ตั้งค่ารูป QR ชำระเงิน กรุณาติดต่อร้าน
                    </div>
                @endif

                <dl class="mt-5 grid gap-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-black/50 dark:text-white/50">บริการ</dt><dd class="text-right font-bold">{{ $booking->service->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-black/50 dark:text-white/50">วันและเวลา</dt><dd class="text-right font-bold">{{ $booking->booking_date->format('d/m/Y') }} {{ substr($booking->start_time, 0, 5) }} น.</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-black/50 dark:text-white/50">สาขา / ช่าง</dt><dd class="text-right font-bold">{{ $booking->branch->name }} / {{ $booking->employee->name }}</dd></div>
                    <div class="mt-2 flex justify-between gap-4 border-t border-black/10 pt-4 dark:border-white/10"><dt class="text-black/50 dark:text-white/50">ราคาบริการ</dt><dd class="text-right font-bold">฿{{ number_format((float)($payment->subtotal ?? $payment->amount), 2) }}</dd></div>
                    @if((float)$payment->discount_amount > 0)
                        <div class="flex justify-between gap-4"><dt class="text-black/50 dark:text-white/50">ส่วนลด{{ $payment->couponUsage?->coupon ? ' · '.$payment->couponUsage->coupon->name : '' }}</dt><dd class="text-right font-black text-brand">−฿{{ number_format((float)$payment->discount_amount, 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4 text-base"><dt class="font-black">ยอดต้องชำระครั้งนี้</dt><dd class="text-right font-black">฿{{ number_format((float)$payment->amount, 2) }}</dd></div>
                </dl>
            </div>

            <div class="card">
                <p class="text-xs font-black tracking-widest text-brand">UPLOAD SLIP</p>
                <h2 class="mt-2 text-2xl font-black">อัปโหลดสลิปเพื่อยืนยัน</h2>
                <p class="mt-2 text-sm text-black/55 dark:text-white/55">ระบบจะอ่าน QR บนสลิป ตรวจสอบยอด ฿{{ number_format((float) $payment->amount, 2) }} และป้องกันการใช้สลิปซ้ำ</p>

                @if($payment->status !== 'verified' && $payment->expires_at)
                    <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl bg-black px-5 py-4 text-white" data-payment-countdown data-expires-at="{{ $payment->expires_at->toIso8601String() }}" data-dashboard-url="{{ route('member.dashboard') }}">
                        <div>
                            <p class="text-[10px] font-black tracking-[.18em] text-red-300">PAYMENT EXPIRES IN</p>
                            <p class="mt-1 text-sm text-white/60">กรุณาชำระเงินและอัปโหลดสลิปภายในเวลาที่กำหนด</p>
                        </div>
                        <span class="min-w-24 text-right text-3xl font-black tabular-nums text-red-400" data-countdown-time>10:00</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mt-5 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-bold text-brand">{{ $errors->first() }}</div>
                @endif

                @if($payment->status === 'verified')
                    <div class="mt-6 rounded-2xl border border-emerald-300 bg-emerald-50 p-5 text-emerald-800">
                        <p class="font-black">✓ ชำระเงินและยืนยันการจองแล้ว</p>
                        <p class="mt-1 text-sm">เลขอ้างอิง {{ $payment->transaction_ref }}</p>
                    </div>
                @else
                    <form class="mt-6 grid gap-4" method="POST" action="{{ route('payments.verify', $booking) }}" enctype="multipart/form-data" data-slip-form>
                        @csrf
                        <label class="block cursor-pointer rounded-2xl border-2 border-dashed border-black/15 p-6 text-center transition hover:border-brand dark:border-white/15" data-slip-dropzone>
                            <input class="sr-only" type="file" name="slip" accept="image/jpeg,image/png,image/webp" required data-slip-input>
                            <span class="block text-3xl">▣</span>
                            <span class="mt-2 block font-black" data-slip-label>เลือกรูปสลิป</span>
                            <span class="mt-1 block text-xs text-black/45 dark:text-white/45">JPG, PNG หรือ WEBP ขนาดไม่เกิน 5 MB</span>
                        </label>
                        <img class="hidden max-h-72 w-full rounded-2xl border border-black/10 object-contain" alt="ตัวอย่างสลิป" data-slip-preview>
                        <input type="hidden" name="qr_data" data-slip-qr>
                        <p class="hidden rounded-xl px-4 py-3 text-sm font-bold" data-slip-status></p>
                        <button class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-40" type="submit" disabled data-slip-submit>
                            ตรวจสอบและยืนยันการชำระเงิน
                        </button>
                    </form>
                @endif

                <a class="mt-5 inline-flex text-sm font-bold text-brand underline" href="{{ route('member.dashboard') }}">กลับสู่ Dashboard</a>
            </div>
        </div>
    </div>
</section>
@endsection
