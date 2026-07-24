<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'Smart Cut' }}</title>
    <link rel="stylesheet" href="https://cdn.boxicons.com/3.0.8/fonts/filled/boxicons-filled.min.css">
    @vite(['resources/css/app.css','resources/ts/app.ts'])
</head>
<body>
<header class="border-b border-black/10 bg-white/85 backdrop-blur dark:border-white/10 dark:bg-neutral-950/85">
    <div class="shell flex h-18 items-center justify-between py-4">
        <a href="{{ route('home') }}" class="text-lg font-black tracking-tight">SM <span class="text-brand">HAIR DESIGN</span><img src="{{ asset('images/scissors-modern.svg') }}" width="22" height="22" class="ml-2 inline-block align-[-3px]" alt=""></a>
        <nav class="hidden gap-6 md:flex">
            <a class="nav-link" href="{{ route('members.find') }}">ค้นหาสมาชิก</a>
            <a class="nav-link" href="{{ route('bookings.create') }}">จองคิว</a>
            <a class="nav-link" href="{{ route('admin.login') }}">สำหรับพนักงาน</a>
        </nav>
        <button data-theme-toggle class="rounded-lg border border-black/10 px-3 py-2 text-xs font-bold dark:border-white/20">◐</button>
    </div>
</header>
@if(session('success'))<div data-toast class="fixed right-5 top-20 z-50 rounded-xl bg-ink px-5 py-3 text-sm font-bold text-white shadow-xl">{{ session('success') }}</div>@endif
@if(session('error'))<div data-toast class="fixed right-5 top-20 z-50 rounded-xl bg-brand px-5 py-3 text-sm font-bold text-white shadow-xl">{{ session('error') }}</div>@endif
<main>@yield('content')</main>
<footer class="mt-16 border-t border-black/10 py-8 text-center text-xs text-black/50 dark:border-white/10 dark:text-white/50">© {{ date('Y') }} SM HAIR DESIGN — Premium Hair Experience</footer>
</body>
</html>
