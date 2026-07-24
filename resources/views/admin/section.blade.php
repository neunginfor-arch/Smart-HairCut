@extends('layouts.app')

@section('content')
<section class="shell py-12">
    <p class="text-xs font-black tracking-widest text-brand">ADMIN CONSOLE</p>
    <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-4xl font-black">{{ $title }}</h1>
            <p class="mt-2 text-sm text-black/55 dark:text-white/55">{{ $description }}</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn border border-black/15 dark:border-white/20">← Dashboard</a>
    </div>

    <div class="card mt-7 overflow-x-auto">
        <div class="mb-4 text-sm text-black/50 dark:text-white/50">ทั้งหมด {{ number_format($records->total()) }} รายการ</div>
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-black/10 text-xs text-black/50 dark:border-white/50">
                <tr>
                    @foreach($columns as $column)
                        <th class="p-3">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-b border-black/5 dark:border-white/5">
                        @foreach($row as $cell)
                            @if($section === 'notifications' && $loop->index === 3)
                                <td class="p-3">
                                    @if($cell === 'sent')
                                        <span class="inline-flex items-center gap-2 font-bold text-emerald-600 dark:text-emerald-400">
                                            <span class="inline-flex size-5 items-center justify-center rounded-full bg-emerald-100 text-xs dark:bg-emerald-500/20">✓</span>
                                            ส่งสำเร็จ
                                        </span>
                                    @elseif($cell === 'failed')
                                        <span class="inline-flex items-center gap-2 font-bold text-brand">
                                            <span class="inline-flex size-5 items-center justify-center rounded-full bg-red-100 text-xs dark:bg-red-500/20">×</span>
                                            ส่งไม่สำเร็จ
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-2 font-bold text-amber-600 dark:text-amber-400">
                                            <span class="inline-flex size-5 items-center justify-center rounded-full bg-amber-100 text-xs dark:bg-amber-500/20">•</span>
                                            รอส่ง
                                        </span>
                                    @endif
                                </td>
                            @else
                                <td class="max-w-80 break-words p-3">{{ $cell }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="p-8 text-center text-black/45 dark:text-white/45">ยังไม่มีข้อมูล</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($records->hasPages())
            <div class="mt-5">{{ $records->links() }}</div>
        @endif
    </div>
</section>
@endsection
