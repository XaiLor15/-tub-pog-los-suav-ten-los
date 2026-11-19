{{-- Donation tracking — artboard "Donations" --}}
@php
    $t = $totals;
    $chips = [
        ['value' => '', 'label' => 'ທັງໝົດ'], ['value' => 'unpaid', 'label' => 'ຍັງບໍ່ຈ່າຍ'], ['value' => 'partial', 'label' => 'ບາງສ່ວນ'],
        ['value' => 'paid', 'label' => 'ຄົບແລ້ວ'], ['value' => 'over', 'label' => 'ເກີນ'],
    ];
@endphp
<x-layouts.app title="ຕິດຕາມການບໍລິຈາກ" :crumbs="['ກອງທຶນ', 'ຕິດຕາມການບໍລິຈາກ']">
    <p class="print-only mb-4 text-sm">ລາຍງານການບໍລິຈາກ · {{ $event?->title }} · ພິມວັນທີ {{ lao_date(now(), true) }}</p>
    <x-page-header title="ຕິດຕາມການບໍລິຈາກ" icon="hand-coins" description="ຍອດຄາດຫວັງ, ຊຳລະແລ້ວ ແລະ ຄ້າງຊຳລະ ຂອງແຕ່ລະຄົນ ຕໍ່ກິດຈະກຳ">
        <x-slot:actions>
            <form method="GET" class="w-64"><x-select name="event" id="filter-event" :options="$events->pluck('title', 'id')->all()" :selected="$event?->id" data-autosubmit aria-label="ເລືອກກິດຈະກຳ" /></form>
            <button type="button" class="btn-outline" data-print>{{ icon('printer', 'size-4') }} ພິມ</button>
            @if ($event)<a href="{{ route('donations.export', ['event' => $event->id]) }}" class="btn-outline">{{ icon('file-down', 'size-4') }} Export CSV</a>@endif
            @if ($canManage && $event)
                <button type="button" class="btn-primary" data-modal-open="pay-form" data-action="{{ route('donations.payments.store') }}" data-title="ບັນທຶກການຊຳລະ" data-fill='{"paid_on":"{{ now()->toDateString() }}","method":"cash"}'>{{ icon('plus', 'size-4') }} ບັນທຶກການຊຳລະ</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (! $event)
        <div class="card"><x-empty icon="hand-coins" message="ຍັງບໍ່ມີກິດຈະກຳ — ສ້າງກິດຈະກຳກ່ອນ ເພື່ອຕິດຕາມການບໍລິຈາກ"><a href="{{ route('activities.index', ['new' => 1]) }}" class="btn-primary btn-sm">ສ້າງກິດຈະກຳ</a></x-empty></div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" data-stagger>
            <x-stat-card label="ຍອດຄາດຫວັງ" icon="clipboard-list" :value="money($t['expected'])" :hint="'ຈາກ '.$t['count'].' ຜູ້ບໍລິຈາກ'" />
            <x-stat-card label="ຊຳລະແລ້ວ" icon="circle-check" :value="money($t['paid'])" tone="success" :hint="$t['pct'] === null ? '—' : percent($t['pct']).' ຂອງເປົ້າໝາຍ'" />
            <x-stat-card label="ຄ້າງຊຳລະ" icon="circle-alert" :value="money($t['outstanding'])" tone="destructive" :hint="$t['outstanding_people'].' ຄົນຍັງຄ້າງ'" />
            <div class="card p-5">
                <div class="flex items-start justify-between gap-2">
                    <span class="stat-icon size-10 rounded-xl">{{ icon('chart-no-axes-column', 'size-5') }}</span>
                    <span class="num text-kpi font-bold tracking-tight">{{ $t['pct'] === null ? '—' : percent($t['pct'], 0) }}</span>
                </div>
                <p class="mt-3 text-sm font-medium text-muted">ຄວາມຄືບໜ້າລວມ</p>
                <x-progress :value="$t['pct']" class="mt-2.5 h-2.5" tone="bg-primary" />
                <p class="mt-3 text-[0.8125rem] text-muted">ຄົບ {{ $t['paid_count'] + $t['over'] }} · ບາງສ່ວນ {{ $t['partial'] }} · ຍັງບໍ່ຈ່າຍ {{ $t['unpaid'] }}</p>
            </div>
        </div>

        <x-info-banner class="mt-6">ທຸກການຊຳລະຈະຖືກບັນທຶກເປັນ <b class="text-primary">ລາຍຮັບ</b> ໃນໜ້າການເງິນໂດຍອັດຕະໂນມັດ ແລະ ນັບພຽງຄັ້ງດຽວ. ແກ້ໄຂ ຫຼື ລຶບໄດ້ຈາກໜ້ານີ້ເທົ່ານັ້ນ.</x-info-banner>

        <div class="card mt-6 overflow-hidden">
            <form method="GET" class="no-print flex flex-col gap-3 border-b border-border p-3 lg:flex-row lg:items-center lg:justify-between">
                <input type="hidden" name="event" value="{{ $event->id }}">
                <x-chips param="status" :options="$chips" :current="$status" label="ສະຖານະ" />
                <div class="flex gap-2">
                    <x-search placeholder="ຄົ້ນຫາຜູ້ບໍລິຈາກ" class="lg:w-64" />
                    @if ($canManage)<button type="button" class="btn-outline whitespace-nowrap" data-modal-open="pledge-form">{{ icon('user-round-plus', 'size-4') }} ຜູ້ບໍລິຈາກ</button>@endif
                </div>
            </form>
            @if ($rows->isEmpty())
                <x-empty icon="hand-coins" :message="$t['count'] ? 'ບໍ່ພົບຜູ້ບໍລິຈາກທີ່ກົງກັບຕົວກອງ' : 'ຍັງບໍ່ມີຜູ້ບໍລິຈາກສຳລັບກິດຈະກຳນີ້'" />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead><tr><th>ຜູ້ບໍລິຈາກ</th><th class="text-right">ຄາດຫວັງ</th><th class="text-right">ຊຳລະແລ້ວ</th><th class="text-right">ຄ້າງ</th><th class="min-w-44">ຄວາມຄືບໜ້າ</th><th>ຊຳລະລ່າສຸດ</th><th>ສະຖານະ</th>@if ($canManage)<th class="no-print"></th>@endif</tr></thead>
                        <tbody>
                        @foreach ($rows as $r)
                            @php [$label, $tone] = \App\Services\FinanceService::statusLabel($r['status']); $pid = $r['pledge']->id; $hist = $payments->get($pid, collect()); @endphp
                            <tr>
                                <td class="whitespace-nowrap"><span class="flex items-center gap-3"><x-avatar :member="$r['member']" size="sm" /><span class="font-semibold">{{ $r['member']?->name }}</span></span></td>
                                <td class="num text-right whitespace-nowrap">{{ $r['expected'] ? money($r['expected']) : '—' }}</td>
                                <td class="num text-right whitespace-nowrap">{{ money($r['paid']) }}</td>
                                <td class="num text-right whitespace-nowrap {{ $r['outstanding'] ? 'text-destructive' : '' }}">{{ money($r['outstanding']) }}</td>
                                <td><span class="flex items-center gap-3"><x-progress :value="$r['pct']" :tone="$r['pct'] !== null && $r['pct'] >= 100 ? 'bg-success' : 'bg-primary'" class="w-24" /><span class="num w-12 text-right text-sm font-semibold">{{ $r['pct'] === null ? '—' : percent($r['pct'], 0) }}</span></span></td>
                                <td class="num whitespace-nowrap">{{ $r['last_paid_on'] ? lao_date($r['last_paid_on']) : '—' }}</td>
                                <td><x-badge :tone="$tone" dot>{{ $label }}</x-badge></td>
                                @if ($canManage)
                                    <td class="no-print">
                                        <div class="flex justify-end gap-1">
                                            <button type="button" class="btn-outline btn-sm" data-modal-open="pay-form" data-action="{{ route('donations.payments.store') }}" data-title="ບັນທຶກການຊຳລະ — {{ $r['member']?->name }}"
                                                    data-fill="{{ json_encode(['pledge_id' => $pid, 'paid_on' => now()->toDateString(), 'method' => 'cash', 'amount' => $r['outstanding'] ?: null]) }}">{{ icon('plus', 'size-4') }} ຊຳລະ</button>
                                            @if ($hist->isNotEmpty())<button type="button" class="icon-btn-sm" data-toggle="hist-{{ $pid }}" aria-expanded="false" aria-label="ປະຫວັດການຊຳລະ">{{ icon('list', 'size-4') }}</button>@endif
                                            @if ($hist->isEmpty())
                                                <form method="POST" action="{{ route('donations.pledges.destroy', $r['pledge']) }}" data-confirm="ເອົາ {{ $r['member']?->name }} ອອກຈາກລາຍຊື່ຜູ້ບໍລິຈາກ?" data-confirm-danger>@csrf @method('DELETE')
                                                    <button class="icon-btn-sm text-destructive" aria-label="ລຶບຜູ້ບໍລິຈາກ">{{ icon('trash-2', 'size-4') }}</button></form>
                                            @endif
                                            <button type="button" class="icon-btn-sm" data-modal-open="pledge-edit" data-action="{{ route('donations.pledges.update', $r['pledge']) }}" data-method="PUT" data-fill="{{ json_encode(['expected_amount' => $r['expected']]) }}" aria-label="ແກ້ຍອດຄາດຫວັງ">{{ icon('pencil', 'size-4') }}</button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                            @if ($canManage && $hist->isNotEmpty())
                                <tr id="hist-{{ $pid }}" class="hidden bg-surface-2/40"><td colspan="8">
                                    <p class="mb-2 text-sm font-semibold">ປະຫວັດການຊຳລະ — {{ $r['member']?->name }}</p>
                                    @foreach ($hist as $p)
                                        <div class="flex flex-wrap items-center gap-3 py-1 text-sm">
                                            <span class="num w-24">{{ lao_date($p->paid_on) }}</span><span class="num w-32 font-semibold">{{ money($p->amount) }}</span><span class="w-36">{{ $p->methodLabel() }}</span>
                                            <span class="flex-1 text-muted">{{ $p->note }}</span>
                                            @if ($p->slip_path)<a href="{{ route('media.slip', $p) }}" target="_blank" class="link">ໃບບິນ</a>@endif
                                            <button type="button" class="icon-btn-sm size-8" data-modal-open="pay-edit" data-action="{{ route('donations.payments.update', $p) }}" data-method="PUT"
                                                    data-fill="{{ json_encode(['amount' => $p->amount, 'paid_on' => $p->paid_on->toDateString(), 'method' => $p->method, 'note' => $p->note]) }}" aria-label="ແກ້ໄຂການຊຳລະ">{{ icon('pencil', 'size-3.5') }}</button>
                                            <form method="POST" action="{{ route('donations.payments.destroy', $p) }}" data-confirm="ລຶບການຊຳລະ {{ money($p->amount) }}? ລາຍຮັບໃນການເງິນຈະຖືກລຶບນຳ." data-confirm-danger data-confirm-label="ລຶບ">@csrf @method('DELETE')
                                                <button class="icon-btn-sm size-8 text-destructive" aria-label="ລຶບການຊຳລະ">{{ icon('trash-2', 'size-3.5') }}</button></form>
                                        </div>
                                    @endforeach
                                </td></tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    @if ($canManage && $event)
        <x-slot:after>
            {{-- Record payment --}}
            <x-dialog id="pay-form" title="ບັນທຶກການຊຳລະ" :open="$errors->any() && old('_form') === 'pay'">
                <form method="POST" action="{{ route('donations.payments.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col" data-fillable data-busy>
                    @csrf <input type="hidden" name="_method" value="POST" data-default="POST"><input type="hidden" name="_form" value="pay">
                    <input type="hidden" name="client_request_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <input type="hidden" name="event_id" value="{{ $event->id }}">
                    <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                        <x-field name="pledge_id" label="ຜູ້ບໍລິຈາກ" required>
                            <x-select name="pledge_id" :options="$rows->mapWithKeys(fn ($r) => [$r['pledge']->id => $r['member']?->name])->all()" :selected="old('pledge_id')" placeholder="— ເລືອກ —" />
                        </x-field>
                        <details class="rounded-lg border border-border p-3 text-sm">
                            <summary class="cursor-pointer font-semibold">ຜູ້ບໍລິຈາກໃໝ່ (ບໍ່ມີຍອດຄາດຫວັງ)</summary>
                            <div class="mt-3" data-member-picker><input type="hidden" name="member_id" data-picker-value><input class="input" placeholder="ພິມຊື່ສະມາຊິກ..." data-picker-input aria-label="ສະມາຊິກ"></div>
                            <p class="hint">ໃຊ້ເມື່ອຜູ້ບໍລິຈາກບໍ່ຢູ່ໃນລາຍຊື່ — ລະບົບຈະເພີ່ມໃຫ້ອັດຕະໂນມັດ</p>
                        </details>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field name="amount" label="ຈຳນວນເງິນ (₭)" required>
                                <input id="f-amount" type="number" name="amount" min="1" step="1" inputmode="numeric" value="{{ old('amount') }}" class="input num @error('amount') input-error @enderror" data-money-preview="pay-preview">
                                <p id="pay-preview" class="hint"></p>
                            </x-field>
                            <x-field name="paid_on" label="ວັນທີຊຳລະ" required><input id="f-paid_on" type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" class="input"></x-field>
                            <x-field name="method" label="ວິທີຊຳລະ" required><x-select name="method" :options="\App\Support\Lookups::PAYMENT_METHODS" :selected="old('method', 'cash')" /></x-field>
                            <x-field name="note" label="ໝາຍເຫດ"><input id="f-note" name="note" value="{{ old('note') }}" maxlength="255" class="input"></x-field>
                        </div>
                        <x-field name="slip" label="ໃບສຳລັດຊຳລະ (ທາງເລືອກ · ເກັບແບບສ່ວນຕົວ)"><input type="file" name="slip" accept="image/jpeg,image/png,image/webp" class="input py-2" data-preview="slip-preview"><p id="slip-preview" class="hint"></p></x-field>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6">
                        <button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button>
                        <button class="btn-primary">ບັນທຶກການຊຳລະ</button>
                    </div>
                </form>
            </x-dialog>

            {{-- Edit payment --}}
            <x-dialog id="pay-edit" title="ແກ້ໄຂການຊຳລະ" size="sm">
                <form method="POST" action="#" class="flex min-h-0 flex-1 flex-col" data-fillable data-busy>
                    @csrf <input type="hidden" name="_method" value="PUT" data-default="PUT">
                    <div class="flex-1 space-y-4 p-5 sm:p-6">
                        <x-field name="amount" label="ຈຳນວນເງິນ (₭)" required><input type="number" name="amount" min="1" step="1" class="input num"></x-field>
                        <x-field name="paid_on" label="ວັນທີຊຳລະ" required><input type="date" name="paid_on" class="input"></x-field>
                        <div><label for="edit-method" class="label">ວິທີຊຳລະ</label><x-select name="method" id="edit-method" :options="\App\Support\Lookups::PAYMENT_METHODS" /></div>
                        <x-field name="note" label="ໝາຍເຫດ"><input name="note" maxlength="255" class="input"></x-field>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6"><button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button><button class="btn-primary">ບັນທຶກ</button></div>
                </form>
            </x-dialog>

            {{-- Add pledge --}}
            <x-dialog id="pledge-form" title="ເພີ່ມຜູ້ບໍລິຈາກ" size="sm" :open="$errors->has('member_id') && old('_form') === 'pledge'">
                <form method="POST" action="{{ route('donations.pledges.store') }}" class="flex min-h-0 flex-1 flex-col" data-busy>
                    @csrf <input type="hidden" name="_form" value="pledge"><input type="hidden" name="event_id" value="{{ $event->id }}">
                    <div class="flex-1 space-y-4 p-5 sm:p-6">
                        <x-field name="member_id" label="ສະມາຊິກ" required><div data-member-picker><input type="hidden" name="member_id" data-picker-value><input class="input @error('member_id') input-error @enderror" placeholder="ພິມຊື່..." data-picker-input></div></x-field>
                        <x-field name="expected_amount" label="ຍອດຄາດຫວັງ (₭)" hint="ເວັ້ນວ່າງ = ບໍ່ກຳນົດ"><input id="f-expected_amount" type="number" name="expected_amount" min="0" step="1" class="input num" data-money-preview="exp-preview"><p id="exp-preview" class="hint"></p></x-field>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6"><button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button><button class="btn-primary">ເພີ່ມ</button></div>
                </form>
            </x-dialog>

            {{-- Edit pledge --}}
            <x-dialog id="pledge-edit" title="ແກ້ໄຂຍອດຄາດຫວັງ" size="sm">
                <form method="POST" action="#" class="flex min-h-0 flex-1 flex-col" data-fillable data-busy>
                    @csrf <input type="hidden" name="_method" value="PUT" data-default="PUT">
                    <div class="flex-1 space-y-4 p-5 sm:p-6">
                        <x-field name="expected_amount" label="ຍອດຄາດຫວັງ (₭)" hint="ເວັ້ນວ່າງ = ບໍ່ກຳນົດ"><input type="number" name="expected_amount" min="0" step="1" class="input num"></x-field>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6"><button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button><button class="btn-primary">ບັນທຶກ</button></div>
                </form>
            </x-dialog>
        </x-slot:after>
    @endif
</x-layouts.app>
