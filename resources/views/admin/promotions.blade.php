@extends('layouts.app')

@section('title', 'จัดการโปรโมชั่น')

@section('content')
<section class="shell py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-black tracking-[.2em] text-brand">PROMOTION BANNERS</p>
            <h1 class="mt-2 text-4xl font-black">จัดการโปรโมชั่น</h1>
            <p class="mt-2 text-sm text-black/55 dark:text-white/55">เพิ่ม แก้ไข จัดลำดับ และเปิดหรือปิดแบนเนอร์บน Member Area</p>
        </div>
        <a class="btn border border-black/15 dark:border-white/20" href="{{ route('admin.dashboard') }}">← Admin Dashboard</a>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-2xl border border-red-300 bg-red-50 px-5 py-4 text-sm font-bold text-brand">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-7 grid gap-6 xl:grid-cols-[380px_1fr]">
        <aside class="card h-fit">
            <p class="text-xs font-black tracking-widest text-brand">NEW PROMOTION</p>
            <h2 class="mt-2 text-2xl font-black">เพิ่มแบนเนอร์</h2>

            <form class="mt-6 grid gap-4" method="POST" action="{{ route('admin.promotions.store') }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="label">ชื่อโปรโมชั่น *</label>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="255" required>
                </div>
                <div>
                    <label class="label">รายละเอียด</label>
                    <textarea name="description" rows="3" maxlength="1000">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="label">รูปแบนเนอร์</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <p class="mt-2 text-xs text-black/45 dark:text-white/45">JPG, PNG หรือ WEBP ไม่เกิน 5 MB แนะนำขนาด 1600 × 600 px</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">ข้อความปุ่ม</label>
                        <input type="text" name="button_text" value="{{ old('button_text') }}" maxlength="50" placeholder="ดูรายละเอียด">
                    </div>
                    <div>
                        <label class="label">ลำดับ</label>
                        <input type="number" name="display_order" value="{{ old('display_order', 0) }}" min="0" max="999">
                    </div>
                </div>
                <div>
                    <label class="label">ลิงก์เมื่อกดปุ่ม</label>
                    <input type="url" name="button_url" value="{{ old('button_url') }}" maxlength="2048" placeholder="https://...">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">วันที่เริ่ม *</label>
                        <input type="date" name="starts_at" value="{{ old('starts_at', today()->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="label">วันที่สิ้นสุด *</label>
                        <input type="date" name="ends_at" value="{{ old('ends_at', today()->addMonth()->format('Y-m-d')) }}" required>
                    </div>
                </div>
                <label class="group flex cursor-pointer items-center justify-between rounded-2xl bg-black/[.04] px-4 py-3 dark:bg-white/[.06]">
                    <span>
                        <span class="block text-sm font-black">เปิดแสดงบนเว็บไซต์</span>
                        <span class="text-xs text-black/45 dark:text-white/45">แสดงเมื่ออยู่ในช่วงวันที่กำหนด</span>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <span class="relative inline-flex">
                        <input class="peer sr-only" type="checkbox" name="is_active" value="1" checked>
                        <span class="h-8 w-14 rounded-full bg-neutral-300 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-4 peer-focus-visible:ring-emerald-200 dark:bg-neutral-700"></span>
                        <span class="pointer-events-none absolute left-1 top-1 size-6 rounded-full bg-white shadow transition-all peer-checked:translate-x-6"></span>
                    </span>
                </label>
                <button class="btn btn-primary w-full">เพิ่มโปรโมชั่น</button>
            </form>
        </aside>

        <div class="grid gap-5">
            @forelse($promotions as $promotion)
                @php
                    $inPeriod = $promotion->starts_at->lte(today()) && $promotion->ends_at->gte(today());
                    $isVisible = $promotion->is_active && $inPeriod;
                @endphp
                <article class="card overflow-hidden p-0">
                    <div class="grid md:grid-cols-[260px_1fr]">
                        <div class="relative min-h-44 bg-black">
                            @if($promotion->image_path)
                                <img class="absolute inset-0 size-full object-cover opacity-75" src="{{ route('promotions.image', $promotion) }}?v={{ $promotion->updated_at->timestamp }}" alt="{{ $promotion->name }}">
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br from-black via-neutral-900 to-red-950"></div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                            <span class="absolute bottom-4 left-4 rounded-full px-3 py-1 text-xs font-black {{ $isVisible ? 'bg-emerald-400 text-black' : 'bg-white/15 text-white' }}">
                                {{ $isVisible ? 'กำลังแสดง' : ($promotion->is_active ? 'อยู่นอกช่วงวันที่' : 'ปิดการแสดง') }}
                            </span>
                        </div>

                        <div class="p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-black tracking-widest text-brand">ORDER {{ $promotion->display_order }}</p>
                                    <h2 class="mt-1 text-xl font-black">{{ $promotion->name }}</h2>
                                    <p class="mt-2 text-sm text-black/55 dark:text-white/55">{{ $promotion->description ?: 'ไม่มีรายละเอียด' }}</p>
                                    <p class="mt-3 text-xs font-bold text-black/45 dark:text-white/45">{{ $promotion->starts_at->format('d/m/Y') }} — {{ $promotion->ends_at->format('d/m/Y') }}</p>
                                </div>

                                <form method="POST" action="{{ route('admin.promotions.toggle', $promotion) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="relative h-8 w-14 rounded-full transition {{ $promotion->is_active ? 'bg-emerald-500' : 'bg-neutral-300 dark:bg-neutral-700' }}" title="{{ $promotion->is_active ? 'ปิดการแสดง' : 'เปิดการแสดง' }}" aria-label="{{ $promotion->is_active ? 'ปิดการแสดง '.$promotion->name : 'เปิดการแสดง '.$promotion->name }}">
                                        <span class="absolute top-1 size-6 rounded-full bg-white shadow transition-all {{ $promotion->is_active ? 'left-7' : 'left-1' }}"></span>
                                    </button>
                                </form>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-black/10 pt-4 dark:border-white/10">
                                <details class="group flex-1">
                                    <summary class="btn cursor-pointer border border-black/15 px-4 py-2 text-center dark:border-white/20">แก้ไขโปรโมชั่น</summary>
                                    <form class="mt-5 grid gap-4 rounded-2xl bg-black/[.035] p-4 dark:bg-white/[.05]" method="POST" action="{{ route('admin.promotions.update', $promotion) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="label">ชื่อโปรโมชั่น *</label>
                                            <input type="text" name="name" value="{{ $promotion->name }}" maxlength="255" required>
                                        </div>
                                        <div>
                                            <label class="label">รายละเอียด</label>
                                            <textarea name="description" rows="3" maxlength="1000">{{ $promotion->description }}</textarea>
                                        </div>
                                        <div>
                                            <label class="label">เปลี่ยนรูปแบนเนอร์</label>
                                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                                            <p class="mt-2 text-xs text-black/45 dark:text-white/45">แนะนำขนาด 1600 × 600 px</p>
                                            @if($promotion->image_path)
                                                <label class="mt-2 flex items-center gap-2 text-xs font-bold text-brand">
                                                    <input class="size-4 accent-red-600" type="checkbox" name="remove_image" value="1"> ลบรูปปัจจุบัน
                                                </label>
                                            @endif
                                        </div>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div><label class="label">ข้อความปุ่ม</label><input type="text" name="button_text" value="{{ $promotion->button_text }}" maxlength="50"></div>
                                            <div><label class="label">ลำดับ</label><input type="number" name="display_order" value="{{ $promotion->display_order }}" min="0" max="999"></div>
                                        </div>
                                        <div><label class="label">ลิงก์เมื่อกดปุ่ม</label><input type="url" name="button_url" value="{{ $promotion->button_url }}" maxlength="2048" placeholder="https://..."></div>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div><label class="label">วันที่เริ่ม *</label><input type="date" name="starts_at" value="{{ $promotion->starts_at->format('Y-m-d') }}" required></div>
                                            <div><label class="label">วันที่สิ้นสุด *</label><input type="date" name="ends_at" value="{{ $promotion->ends_at->format('Y-m-d') }}" required></div>
                                        </div>
                                        <label class="group flex cursor-pointer items-center justify-between rounded-2xl bg-white px-4 py-3 shadow-sm dark:bg-black/20">
                                            <span>
                                                <span class="block text-sm font-black">เปิดแสดงบนเว็บไซต์</span>
                                                <span class="text-xs text-black/45 dark:text-white/45">แสดงเมื่ออยู่ในช่วงวันที่กำหนด</span>
                                            </span>
                                            <input type="hidden" name="is_active" value="0">
                                            <span class="relative inline-flex">
                                                <input class="peer sr-only" type="checkbox" name="is_active" value="1" @checked($promotion->is_active)>
                                                <span class="h-8 w-14 rounded-full bg-neutral-300 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-4 peer-focus-visible:ring-emerald-200 dark:bg-neutral-700"></span>
                                                <span class="pointer-events-none absolute left-1 top-1 size-6 rounded-full bg-white shadow transition-all peer-checked:translate-x-6"></span>
                                            </span>
                                        </label>
                                        <button class="btn btn-primary">บันทึกการแก้ไข</button>
                                    </form>
                                </details>

                                <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" onsubmit="return confirm('ยืนยันการลบโปรโมชั่นนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn border border-red-300 px-4 py-2 text-brand">ลบ</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card py-16 text-center">
                    <p class="text-4xl">✦</p>
                    <h2 class="mt-3 text-xl font-black">ยังไม่มีโปรโมชั่น</h2>
                    <p class="mt-2 text-sm text-black/50 dark:text-white/50">เพิ่มโปรโมชั่นแรกจากแบบฟอร์มด้านซ้าย</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
