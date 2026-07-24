<section class="card mt-8 overflow-hidden p-0">
    <div class="border-b border-black bg-black px-6 py-6 text-white sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-black tracking-[.2em] text-brand">BOOKING</p>
                <h2 class="mt-2 text-2xl font-black">เลือกเวลาที่ใช่สำหรับคุณ</h2>
                <p class="mt-2 text-sm text-white/60">กรอกข้อมูลด้านล่างเพื่อดูช่วงเวลาว่างของช่าง</p>
            </div>
            <a class="text-sm font-bold text-red-300 transition hover:translate-x-1 hover:text-white" href="{{ route('bookings.create') }}">หน้าจองแบบเต็ม →</a>
        </div>
    </div>

    <form method="POST" action="{{ route('bookings.store') }}" class="min-w-0 bg-white p-6 dark:bg-neutral-900 sm:p-8">
        @csrf
        <div class="grid min-w-0 gap-x-5 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
            <div class="min-w-0">
                <label class="label"><span class="mr-2 text-brand">01</span>สาขา</label>
                <select name="branch_id" data-booking-branch>
                    @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="label"><span class="mr-2 text-brand">02</span>ช่าง</label>
                <select name="employee_id" data-booking-employee>
                    @foreach($employees as $employee)<option value="{{ $employee->id }}" data-branch-id="{{ $employee->branch_id }}">{{ $employee->name }}</option>@endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="label"><span class="mr-2 text-brand">03</span>บริการ</label>
                <select name="service_id">
                    @foreach($services as $service)<option value="{{ $service->id }}">{{ $service->name }} — {{ $service->duration_minutes }} นาที</option>@endforeach
                </select>
            </div>
            <div class="min-w-0 overflow-hidden">
                <label class="label"><span class="mr-2 text-brand">04</span>วันที่</label>
                <input class="booking-date-input block min-w-0 max-w-full" name="booking_date" data-booking-date type="date" min="{{ today()->format('Y-m-d') }}" value="{{ today()->format('Y-m-d') }}">
                <p data-booking-format class="mt-2 text-xs text-black/50 dark:text-white/50"></p>
            </div>
        </div>

        <input name="start_time" data-booking-time type="hidden">
        <div class="mt-8 border-t border-black/10 pt-6 dark:border-white/10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black tracking-[.2em] text-brand">05 / SELECT TIME</p>
                    <h3 class="mt-1 font-black">ช่วงเวลาว่าง</h3>
                </div>
                <p class="text-xs text-black/50 dark:text-white/50"><span class="mr-1 inline-block size-2 rounded-full bg-black dark:bg-white"></span>ว่าง <span class="ml-3 mr-1 inline-block size-2 rounded-full bg-neutral-300"></span>ไม่ว่าง</p>
            </div>
            <div data-booking-slots class="mt-5 grid grid-cols-3 gap-3 sm:grid-cols-5"></div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-black/[.035] p-4 dark:bg-white/[.06]">
            <p class="text-sm text-black/55 dark:text-white/55">กดเลือกช่วงเวลา แล้วจึงยืนยันการจอง</p>
            <button data-booking-submit class="btn btn-primary disabled:cursor-not-allowed disabled:opacity-40" disabled>ยืนยันการจอง</button>
        </div>
    </form>
</section>

<script>
const bwR = document.querySelector('[data-booking-branch]');
const bwE = document.querySelector('[data-booking-employee]');
const bwD = document.querySelector('[data-booking-date]');
const bwS = document.querySelector('[data-booking-slots]');
const bwT = document.querySelector('[data-booking-time]');
const bwB = document.querySelector('[data-booking-submit]');
const bwF = document.querySelector('[data-booking-format]');

function bwShowEmployees(employeeIds) {
    const current = bwE.value;
    let first = null;
    [...bwE.options].forEach(option => {
        const visible = option.dataset.branchId === bwR.value && employeeIds.includes(String(option.value));
        option.hidden = !visible;
        option.disabled = !visible;
        if (visible && !first) first = option;
    });
    bwE.disabled = !first;
    const currentStillAvailable = [...bwE.options].find(option => option.value === current && !option.disabled);
    bwE.value = (currentStillAvailable || first)?.value || '';
}

async function bwRefreshEmployees() {
    bwE.disabled = true;
    const url = '{{ route('bookings.available-employees') }}?branch_id=' + encodeURIComponent(bwR.value) + '&date=' + encodeURIComponent(bwD.value);
    try {
        const response = await fetch(url, {headers: {Accept: 'application/json'}});
        if (!response.ok) throw new Error('Unable to load employees');
        const data = await response.json();
        bwShowEmployees((data.employee_ids || []).map(String));
    } catch (error) {
        bwShowEmployees([...bwE.options]
            .filter(option => option.dataset.branchId === bwR.value)
            .map(option => String(option.value)));
    }
}

async function bwLoad() {
    const [year, month, day] = bwD.value.split('-');
    bwF.textContent = year ? day + '/' + month + '/' + year : '';
    bwT.value = '';
    bwB.disabled = true;

    if (!bwE.value) {
        bwS.innerHTML = '<p class="col-span-full py-5 text-center text-sm text-brand">ไม่มีช่างที่พร้อมให้บริการในสาขาและวันที่เลือก</p>';
        return;
    }

    bwS.innerHTML = '<p class="col-span-full py-5 text-center text-sm text-black/45">กำลังโหลดช่วงเวลาว่าง…</p>';
    const url = '{{ route('bookings.slots') }}?employee_id=' + encodeURIComponent(bwE.value) + '&date=' + encodeURIComponent(bwD.value);
    const response = await fetch(url, {headers: {Accept: 'application/json'}});
    if (!response.ok) {
        bwS.innerHTML = '<p class="col-span-full py-5 text-center text-sm text-brand">ไม่สามารถโหลดช่วงเวลาได้</p>';
        return;
    }

    const data = await response.json();
    bwS.innerHTML = '';
    if (data.closed) {
        const notice = document.createElement('p');
        notice.className = 'col-span-full rounded-xl border border-brand/30 bg-brand/10 px-4 py-3 text-center text-sm font-bold text-brand';
        notice.textContent = data.message;
        bwS.appendChild(notice);
    }

    data.slots.forEach(slot => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = slot.time;
        button.disabled = !slot.available;
        button.className = 'rounded-xl border p-3 font-bold transition ' + (slot.available
            ? 'border-black bg-black text-white hover:-translate-y-0.5 dark:border-white dark:bg-white dark:text-black'
            : 'cursor-not-allowed border-neutral-200 bg-neutral-100 text-neutral-400 dark:border-neutral-800 dark:bg-neutral-800');
        if (slot.available) {
            button.onclick = () => {
                bwS.querySelectorAll('button').forEach(item => item.classList.remove('ring-4', 'ring-red-200'));
                button.classList.add('ring-4', 'ring-red-200');
                bwT.value = slot.time;
                bwB.disabled = false;
            };
        }
        bwS.appendChild(button);
    });
}

async function bwReload() {
    await bwRefreshEmployees();
    await bwLoad();
}

bwR.addEventListener('change', bwReload);
bwD.addEventListener('change', bwReload);
bwE.addEventListener('change', bwLoad);
bwReload();
</script>
