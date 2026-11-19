{{-- Admin — artboard "Admin" (approvals, users, audit log) --}}
@php $me = auth()->user(); @endphp
<x-layouts.app title="ຈັດການຜູ້ໃຊ້" :crumbs="['ລະບົບ', 'ຈັດການຜູ້ໃຊ້']">
    <x-page-header title="ຈັດການຜູ້ໃຊ້" description="ອະນຸມັດບັນຊີໃໝ່, ກຳນົດ Role ແລະ ເຊື່ອມບັນຊີກັບຂໍ້ມູນສະມາຊິກ">
        <x-slot:actions>
            <div class="segmented">
                <a href="{{ route('admin.index') }}" class="segment" @if ($tab === 'users') aria-current="page" @endif>ຜູ້ໃຊ້</a>
                <a href="{{ route('admin.index', ['tab' => 'audit']) }}" class="segment" @if ($tab === 'audit') aria-current="page" @endif>Audit Log</a>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-destructive/30 bg-destructive-bg p-3 text-destructive" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($tab === 'audit')
        <div class="card">
            <form method="GET" class="flex flex-wrap items-center gap-3 border-b border-border p-3">
                <input type="hidden" name="tab" value="audit">
                <div class="w-64"><x-select name="action" :options="['' => 'ທຸກການກະທຳ'] + collect($actions)->map(fn ($a) => $a[1].' · '.$a[0])->all()" :selected="request('action')" data-autosubmit aria-label="ການກະທຳ" /></div>
            </form>
            <ul class="px-5">
                @forelse ($auditPage as $log)
                    @include('admin._audit-item')
                @empty
                    <x-empty icon="file-text" message="ຍັງບໍ່ມີ Audit log" />
                @endforelse
            </ul>
            @if ($auditPage->hasPages())<div class="flex justify-center border-t border-border p-3">{{ $auditPage->onEachSide(1)->links() }}</div>@endif
        </div>
    @else
        <section class="rounded-xl border border-warning/30 bg-warning-bg/60 p-5" aria-labelledby="pending-h">
            <h2 id="pending-h" class="flex items-center gap-2 text-[1.0625rem] font-semibold">ລໍຖ້າອະນຸມັດ <span class="badge bg-warning-bg text-warning ring-1 ring-warning/30">{{ $pending->count() }}</span></h2>
            @if ($pending->isEmpty())
                <p class="mt-2 text-sm text-muted">ບໍ່ມີບັນຊີລໍຖ້າອະນຸມັດ</p>
            @else
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($pending as $u)
                        @php $match = $suggestions[$u->id]->first(); @endphp
                        <div class="card p-4">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$u->display_name" size="md" />
                                <span class="min-w-0"><span class="block font-bold">{{ $u->display_name }}</span><span class="block truncate text-sm text-muted">{{ $u->email }} · {{ $u->auth_provider === 'google' ? 'Google' : 'Email' }}</span></span>
                            </div>
                            <form method="POST" action="{{ route('admin.approve', $u) }}" class="mt-3">
                                @csrf
                                <p class="text-sm text-muted">ລົງທະບຽນ {{ lao_date($u->created_at) }} · ອາດແມ່ນ:
                                    @if ($match)<b class="text-foreground">{{ $match->name }} · Phaj {{ $match->generation }}</b>@else<b class="text-foreground">ບໍ່ພົບໃນລາຍຊື່</b>@endif</p>
                                <div class="mt-2" data-member-picker>
                                    <input type="hidden" name="member_id" value="{{ $match?->id }}" data-picker-value>
                                    <input class="input" value="{{ $match?->name }}" placeholder="ເຊື່ອມກັບສະມາຊິກ (ທາງເລືອກ)" data-picker-input aria-label="ເຊື່ອມກັບສະມາຊິກ">
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button class="btn-success flex-1">ອະນຸມັດເປັນສະມາຊິກ</button>
                                    <button type="submit" form="reject-{{ $u->id }}" class="btn-outline">ປະຕິເສດ</button>
                                </div>
                            </form>
                            <form id="reject-{{ $u->id }}" method="POST" action="{{ route('admin.reject', $u) }}" data-confirm="ປະຕິເສດບັນຊີ {{ $u->display_name }} ({{ $u->email }})?" data-confirm-danger data-confirm-label="ປະຕິເສດ" hidden>@csrf</form>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="card overflow-hidden">
                <form method="GET" class="flex flex-col gap-3 border-b border-border p-3 sm:flex-row sm:items-center sm:justify-between">
                    <x-chips param="role" :current="$role" label="Role" :options="[['value' => '', 'label' => 'ທັງໝົດ', 'count' => $counts['all']], ['value' => 'member', 'label' => 'ສະມາຊິກ', 'count' => $counts['member']], ['value' => 'admin', 'label' => 'ຜູ້ດູແລ', 'count' => $counts['admin']]]" />
                    @if ($role)<input type="hidden" name="role" value="{{ $role }}">@endif
                    <input name="q" value="{{ request('q') }}" class="input sm:w-64" placeholder="ຄົ້ນຫາຊື່ ຫຼື ອີເມວ" aria-label="ຄົ້ນຫາຜູ້ໃຊ້">
                </form>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead><tr><th>ຜູ້ໃຊ້</th><th>Role</th><th>ເຊື່ອມກັບສະມາຊິກ</th><th>ເຂົ້າລ່າສຸດ</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($users as $u)
                            @php $self = $u->id === $me->id; @endphp
                            <tr class="{{ $u->is_disabled ? 'opacity-60' : '' }}">
                                <td><span class="flex items-center gap-3"><x-avatar :name="$u->display_name" size="sm" />
                                    <span class="leading-tight"><span class="block font-semibold">{{ $u->display_name }} @if ($self)<span class="text-xs font-normal text-muted">(ທ່ານ)</span>@endif @if ($u->is_disabled)<x-badge tone="destructive" class="text-xs">ປິດແລ້ວ</x-badge>@endif</span><span class="text-sm text-muted">{{ $u->email }}</span></span></span></td>
                                <td>
                                    <form method="POST" action="{{ route('admin.role', $u) }}" class="w-36">@csrf @method('PUT')
                                        <x-select name="role" :options="['member' => 'ສະມາຊິກ', 'admin' => 'ຜູ້ດູແລລະບົບ']" :selected="$u->role" :disabled="$self" data-autosubmit aria-label="Role ຂອງ {{ $u->display_name }}" />
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.link', $u) }}" class="flex min-w-48 items-center gap-1">@csrf @method('PUT')
                                        <div data-member-picker class="flex-1"><input type="hidden" name="member_id" value="{{ $u->member_id }}" data-picker-value>
                                            <input class="input {{ $u->member ? '' : 'text-warning' }}" value="{{ $u->member ? $u->member->name.' · Phaj '.$u->member->generation : '' }}" placeholder="— ຍັງບໍ່ເຊື່ອມ" data-picker-input aria-label="ເຊື່ອມ {{ $u->display_name }} ກັບສະມາຊິກ"></div>
                                        <button class="icon-btn-sm" aria-label="ບັນທຶກການເຊື່ອມ">{{ icon('check', 'size-4') }}</button>
                                    </form>
                                </td>
                                <td class="text-sm whitespace-nowrap">{{ $u->last_login_at ? \App\Support\Format::ago($u->last_login_at) : '—' }}</td>
                                <td class="text-right">
                                    @unless ($self)
                                        <form method="POST" action="{{ route('admin.toggle', $u) }}" data-confirm="{{ $u->is_disabled ? 'ເປີດ' : 'ປິດ' }}ບັນຊີ {{ $u->display_name }}?" @unless ($u->is_disabled) data-confirm-danger @endunless>@csrf
                                            <button class="btn-ghost btn-sm {{ $u->is_disabled ? '' : 'text-destructive' }}">{{ $u->is_disabled ? 'ເປີດບັນຊີ' : 'ປິດບັນຊີ' }}</button></form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="users" message="ບໍ່ພົບຜູ້ໃຊ້" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col items-center justify-between gap-2 border-t border-border px-4 py-3 text-[0.8125rem] text-muted sm:flex-row">
                    <span>ທ່ານບໍ່ສາມາດປ່ຽນ Role ຂອງຕົນເອງ, ແລະ ລະບົບຕ້ອງມີຜູ້ດູແລຢ່າງໜ້ອຍ 1 ຄົນສະເໝີ.</span>
                    {{ $users->onEachSide(1)->links() }}
                </div>
            </div>

            <section class="card card-pad" aria-labelledby="audit-h">
                <div class="flex items-center justify-between"><h2 id="audit-h" class="text-[1.0625rem] font-semibold">Audit Log</h2><a href="{{ route('admin.index', ['tab' => 'audit']) }}" class="link text-sm">ທັງໝົດ</a></div>
                <ul class="mt-2">
                    @forelse ($auditRecent as $log)
                        @include('admin._audit-item')
                    @empty
                        <li class="py-4 text-sm text-muted">ຍັງບໍ່ມີ</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @endif
</x-layouts.app>
