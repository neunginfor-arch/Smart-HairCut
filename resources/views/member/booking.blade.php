@extends('layouts.app')

@section('content')
<section class="shell member-page py-12">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-black tracking-widest text-brand">BOOK YOUR MOMENT</p>
            <h1 class="mt-2 text-4xl font-black">จองคิว</h1>
        </div>
        <a class="btn border border-black/15 dark:border-white/20" href="{{ route('member.dashboard') }}">← Member Dashboard</a>
    </div>

    <form method="POST" action="{{ route('bookings.store') }}" class="mt-7 grid gap-6 lg:grid-cols-[1fr_320px]">
        @csrf
        <div class="card">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label">สาขา</label>
                    <select name="branch_id" id="branch" required>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">ช่าง</label>
                    <select name="employee_id" id="employee" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" data-branch-id="{{ $employee->branch_id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">บริการ</label>
                    <select name="service_id" required>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }} — {{ $service->duration_minutes }} นาที</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-0 overflow-hidden">
                    <label class="label">วันที่</label>
                    <input class="booking-date-input block min-w-0 max-w-full" name="booking_date" id="date" type="date" min="{{ today()->format('Y-m-d') }}" value="{{ old('booking_date', today()->format('Y-m-d')) }}" required>
                    <p id="date-format" class="mt-2 text-xs text-black/50 dark:text-white/50"></p>
                </div>
            </div>

            <div class="mt-7 flex items-center justify-between">
                <h2 class="font-black">เลือกเวลา</h2>
                <span class="text-xs"><i class="mr-1 inline-block size-2 rounded-full bg-black dark:bg-white"></i>ว่าง <i class="ml-3 mr-1 inline-block size-2 rounded-full bg-neutral-400"></i>ไม่ว่าง</span>
            </div>
            @error('employee_id')<p class="mt-3 text-sm font-bold text-brand">{{ $message }}</p>@enderror
            @error('start_time')<p class="mt-3 text-sm font-bold text-brand">{{ $message }}</p>@enderror
            <input name="start_time" id="selected-time" type="hidden" value="{{ old('start_time') }}">
            <div id="slot-grid" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
        </div>

        <aside class="card h-fit bg-ink text-white">
            <p class="text-xs font-bold tracking-widest text-red-400">YOUR BOOKING</p>
            <p id="summary" class="mt-6 text-sm text-white/60">เลือกวันที่และเวลาที่สะดวก</p>
            <button id="submit-booking" class="btn mt-8 w-full bg-white text-black disabled:cursor-not-allowed disabled:opacity-40" disabled>ยืนยันการจอง</button>
        </aside>
    </form>
</section>

<script>
const branch = document.querySelector('#branch');
const employee = document.querySelector('#employee');
const date = document.querySelector('#date');
const grid = document.querySelector('#slot-grid');
const selected = document.querySelector('#selected-time');
const submit = document.querySelector('#submit-booking');
const summary = document.querySelector('#summary');
const dateFormat = document.querySelector('#date-format');

function thaiDate() {
    const [year, month, day] = date.value.split('-');
    dateFormat.textContent = year ? 'วันที่เลือก: ' + day + '/' + month + '/' + year : '';
}

function showAvailableEmployees(employeeIds) {
    const current = employee.value;
    let first = null;
    [...employee.options].forEach(option => {
        const visible = option.dataset.branchId === branch.value && employeeIds.includes(String(option.value));
        option.hidden = !visible;
        option.disabled = !visible;
        if (visible && !first) first = option;
    });

    employee.disabled = !first;
    const currentStillAvailable = [...employee.options].find(option => option.value === current && !option.disabled);
    employee.value = (currentStillAvailable || first)?.value || '';
}

async function refreshEmployees() {
    employee.disabled = true;
    const url = '{{ route('bookings.available-employees') }}?branch_id=' + encodeURIComponent(branch.value) + '&date=' + encodeURIComponent(date.value);

    try {
        const response = await fetch(url, {headers: {Accept: 'application/json'}});
        if (!response.ok) throw new Error('Unable to load employees');
        const data = await response.json();
        showAvailableEmployees((data.employee_ids || []).map(String));
    } catch (error) {
        showAvailableEmployees([...employee.options]
            .filter(option => option.dataset.branchId === branch.value)
            .map(option => String(option.value)));
    }
}

async function loadSlots() {
    thaiDate();
    selected.value = '';
    submit.disabled = true;
    summary.textContent = 'เลือกเวลาที่สะดวก';

    if (!employee.value) {
        grid.innerHTML = '<p class="col-span-full py-6 text-center text-sm text-brand">ไม่มีช่างที่พร้อมให้บริการในสาขาและวันที่เลือก</p>';
        return;
    }

    grid.innerHTML = '<p class="col-span-full py-6 text-center text-sm text-black/45">กำลังโหลดเวลาว่าง…</p>';
    const url = '{{ route('bookings.slots') }}?employee_id=' + encodeURIComponent(employee.value) + '&date=' + encodeURIComponent(date.value);
    const response = await fetch(url, {headers: {Accept: 'application/json'}});
    if (!response.ok) {
        grid.innerHTML = '<p class="col-span-full py-6 text-center text-sm text-brand">ไม่สามารถโหลดตารางเวลาได้</p>';
        return;
    }

    const data = await response.json();
    grid.innerHTML = '';
    if (data.closed) {
        const notice = document.createElement('p');
        notice.className = 'col-span-full rounded-xl border border-brand/30 bg-brand/10 px-4 py-3 text-center text-sm font-bold text-brand';
        notice.textContent = data.message;
        grid.appendChild(notice);
        summary.textContent = data.message;
    }

    data.slots.forEach(slot => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = slot.time;
        button.disabled = !slot.available;
        button.className = 'rounded-xl p-4 text-sm font-black ' + (slot.available
            ? 'bg-emerald-500 text-black transition hover:bg-emerald-400'
            : 'cursor-not-allowed bg-neutral-200 text-neutral-400 dark:bg-neutral-800');
        if (slot.available) {
            button.onclick = () => {
                document.querySelectorAll('#slot-grid button').forEach(item => item.classList.remove('ring-4', 'ring-brand'));
                button.classList.add('ring-4', 'ring-brand');
                selected.value = slot.time;
                submit.disabled = false;
                summary.textContent = dateFormat.textContent.replace('วันที่เลือก: ', '') + ' เวลา ' + slot.time + ' น.';
            };
        }
        grid.appendChild(button);
    });
}

async function reloadBooking() {
    await refreshEmployees();
    await loadSlots();
}

branch.addEventListener('change', reloadBooking);
date.addEventListener('change', reloadBooking);
employee.addEventListener('change', loadSlots);
reloadBooking();
</script>
@endsection
