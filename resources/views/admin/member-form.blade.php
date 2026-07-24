@extends('layouts.app')
@section('content')
<section class="shell max-w-3xl py-12"><p class="text-xs font-black tracking-widest text-brand">ADMIN CONSOLE</p><h1 class="mt-2 text-4xl font-black">แก้ไขสมาชิก</h1><form class="card mt-7 grid gap-4 md:grid-cols-3" method="POST" action="{{ route('admin.members.update',$member) }}">@csrf @method('PUT') @include('admin.member-fields',['member'=>$member])<div class="flex gap-3 md:col-span-3"><button class="btn btn-primary">บันทึกการแก้ไข</button><a class="btn border border-black/15 dark:border-white/20" href="{{ route('admin.members') }}">ยกเลิก</a></div></form></section>
@endsection
