{{-- Finance — artboard "Finance" --}}
@php
    $keep = request()->only(['type', 'from', 'to', 'category', 'event']);
    $type = request('type', '');
    $catOptions = ['' => 'ທຸກໝວດ'] + array_combine(\App\Support\Lookups::transactionCategories(), \App\Support\Lookups::transactionCategories());
    $eventOptions = ['' => 'ທຸກກິດຈະກຳ'] + $events->pluck('title', 'id')->all();
@endphp
<x-layouts.app title="ການເງິນ" :crumbs="['ຄັງເງິນ', 'ການເງິນ']">
    <p class="print-only mb-4 text-sm">ລາຍງານການເງິນ · {{ lao_date($from) }} – {{ lao_date($to) }} · ພິມວັນທີ {{ lao_date(now(), true) }}</p>
    <x-page-header title="ການເງິນ" description="ລາຍຮັບ-ລາຍຈ່າຍຂອງຕະກູນ · ຄິດໄລ່ຍອດຈາກຖານຂໍ້ມູນ">
        <x-slot:actions>
            <button type="button" class="btn-outline" data-print>{{ icon('printer', 'size-4') }} ພິມ</button>
            <a href="{{ route('finance.export', $keep) }}" class="btn-outline">{{ icon('download', 'size-4') }} Export CSV</a>
            @if ($canManage)
                <button type="button" class="btn-primary" data-modal-open="tx-form" data-action="{{ route('finance.store') }}" data-title="ບັນທຶກທຸລະກຳ" data-fill='{"date":"{{ now()->toDateString() }}","type":"expense"}'>{{ icon('plus', 'size-4') }} ບັນທຶກທຸລະກຳ</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @unless ($canManage)
        <x-info-banner class="no-print mb-4">ທ່ານເບິ່ງໄດ້ຢ່າງດຽວ — ການບັນທຶກ/ແກ້ໄຂ/ລຶບ ສະເພາະຜູ້ດູແລລະບົບ.</x-info-banner>
    @endunless

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-brand-navy p-5 text-white shadow-soft">
            <p class="text-sm text-white/80">ຍອດຄົງເຫຼືອທັງໝົດ</p>
            <p class="num mt-1 text-[1.875rem] font-bold">{{ money($overall['balance']) }}</p>
            <p class="text-[0.8125rem] text-white/70">ອັບເດດ {{ lao_date(now()) }} · ຈາກ v_finance_summary</p>
        </div>
        <div class="card card-pad">
            <p class="flex items-center gap-2 text-sm text-muted"><span class="inline-flex size-7 items-center justify-center rounded-md bg-success-bg text-success">{{ icon('arrow-down-left', 'size-4') }}</span>ລາຍຮັບ (ຊ່ວງທີ່ເລືອກ)</p>
            <p class="num mt-1 text-2xl font-bold text-success">{{ money($range['income']) }}</p>
            <p class="text-[0.8125rem] text-muted">ລວມເງິນບໍລິຈາກ {{ money($range['donation']) }}</p>
        </div>
        <div class="card card-pad">
            <p class="flex items-center gap-2 text-sm text-muted"><span class="inline-flex size-7 items-center justify-center rounded-md bg-destructive-bg text-destructive">{{ icon('arrow-up-right', 'size-4') }}</span>ລາຍຈ່າຍ (ຊ່ວງທີ່ເລືອກ)</p>
            <p class="num mt-1 text-2xl font-bold text-destructive">{{ money($range['expense']) }}</p>
            <p class="text-[0.8125rem] text-muted">{{ $range['expense_count'] }} ລາຍການ</p>
        </div>
    </div>

    <div class="card mt-6 overflow-hidden">
        <form method="GET" class="no-print flex flex-col gap-3 border-b border-border p-3 lg:flex-row lg:flex-wrap lg:items-center">
            <div class="segmented">
                @foreach (['' => 'ທັງໝົດ', 'income' => 'ລາຍຮັບ', 'expense' => 'ລາຍຈ່າຍ'] as $v => $l)
                    <a href="{{ route('finance.index', array_merge($keep, ['type' => $v ?: null])) }}" class="segment" @if ($type === $v) aria-current="page" @endif>{{ $l }}</a>
                @endforeach
            </div>
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                <input type="date" name="from" value="{{ $from }}" class="input lg:w-40" aria-label="ຈາກວັນທີ" data-autosubmit>
                <span class="text-muted">–</span>
                <input type="date" name="to" value="{{ $to }}" class="input lg:w-40" aria-label="ຫາວັນທີ" data-autosubmit>
            </div>
            <div class="lg:w-40"><x-select name="category" :options="$catOptions" :selected="request('category')" data-autosubmit aria-label="ໝວດ" /></div>
            <div class="lg:w-52"><x-select name="event" :options="$eventOptions" :selected="request('event')" data-autosubmit aria-label="ກິດຈະກຳ" /></div>
        </form>

        @if ($transactions->isEmpty())
            <x-empty icon="wallet" message="ບໍ່ມີທຸລະກຳໃນຊ່ວງທີ່ເລືອກ" />
        @else
            {{-- Mobile: cards --}}
            <ul class="divide-y divide-border md:hidden">
                @foreach ($transactions as $t)
                    <li class="flex items-start justify-between gap-3 px-4 py-3">
                        <span class="min-w-0">
                            <span class="block font-medium">{{ $t->description }}</span>
                            <span class="block text-[0.8125rem] text-muted">{{ lao_date($t->date) }} · {{ $t->category }}{{ $t->event ? ' · '.$t->event->title : '' }}</span>
                            @if ($t->isFromDonation())<span class="badge mt-1 bg-info-bg text-xs text-info">{{ icon('lock', 'size-3') }} ມາຈາກການບໍລິຈາກ</span>@endif
                        </span>
                        <x-money :amount="$t->amount" :sign="$t->type === 'expense' ? '-' : '+'" class="font-semibold" />
                    </li>
                @endforeach
            </ul>
            <div class="hidden overflow-x-auto md:block">
                <table class="data-table">
                    <thead><tr><th>ວັນທີ</th><th>ລາຍລະອຽດ</th><th>ໝວດ</th><th class="hidden md:table-cell">ກິດຈະກຳ</th><th class="text-right">ຈຳນວນ</th><th class="no-print w-28"></th></tr></thead>
                    <tbody>
                    @foreach ($transactions as $t)
                        <tr>
                            <td class="num whitespace-nowrap">{{ lao_date($t->date) }}</td>
                            <td>
                                <span class="font-medium">{{ $t->description }}</span>
                                @if ($t->isFromDonation())<span class="badge mt-1 flex w-fit bg-info-bg text-xs text-info">{{ icon('lock', 'size-3') }} ມາຈາກການບໍລິຈາກ</span>@endif
                            </td>
                            <td class="whitespace-nowrap">{{ $t->category }}</td>
                            <td class="hidden whitespace-nowrap md:table-cell">{{ $t->event?->title ?? '—' }}</td>
                            <td class="text-right font-semibold"><x-money :amount="$t->amount" :sign="$t->type === 'expense' ? '-' : '+'" /></td>
                            <td class="no-print text-right">
                                @if ($t->isFromDonation())
                                    <a href="{{ route('donations.index', ['event' => $t->event_id]) }}" class="link text-sm whitespace-nowrap">ແກ້ທີ່ໜ້າບໍລິຈາກ</a>
                                @elseif ($canManage)
                                    <div class="flex justify-end gap-1">
                                        <button type="button" class="icon-btn-sm border-0" data-modal-open="tx-form" data-action="{{ route('finance.update', $t) }}" data-method="PUT" data-title="ແກ້ໄຂທຸລະກຳ"
                                                data-fill="{{ json_encode(['date' => $t->date->toDateString(), 'description' => $t->description, 'type' => $t->type, 'category' => $t->category, 'amount' => $t->amount, 'event_id' => $t->event_id]) }}" aria-label="ແກ້ໄຂ">{{ icon('pencil', 'size-4') }}</button>
                                        <form method="POST" action="{{ route('finance.destroy', $t) }}" data-confirm="ລຶບ “{{ $t->description }}” {{ money($t->amount) }}? ການລຶບຈະຖືກບັນທຶກໃນ Audit log." data-confirm-danger data-confirm-label="ລຶບ">@csrf @method('DELETE')
                                            <button class="icon-btn-sm border-0 text-destructive" aria-label="ລຶບ">{{ icon('trash-2', 'size-4') }}</button></form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row">
                <span class="text-muted">ສະແດງ {{ $transactions->count() }} ຈາກ {{ $transactions->total() }} ລາຍການ</span>
                <span class="no-print">{{ $transactions->onEachSide(1)->links() }}</span>
                <span>ຍອດສຸດທິຂອງຊ່ວງ: <b class="num">{{ money($range['net']) }}</b></span>
            </div>
        @endif
    </div>

    @if ($canManage)
        <x-slot:after>
            <x-dialog id="tx-form" title="ບັນທຶກທຸລະກຳ" :open="$openForm">
                <form method="POST" action="{{ route('finance.store') }}" class="flex min-h-0 flex-1 flex-col" data-fillable data-similar-tx data-busy>
                    @csrf <input type="hidden" name="_method" value="POST" data-default="POST"><input type="hidden" name="_form" value="tx">
                    <input type="hidden" name="client_request_id" value="{{ old('client_request_id', (string) \Illuminate\Support\Str::uuid()) }}">
                    <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                        <div class="segmented grid grid-cols-2">
                            <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="type" value="income" class="sr-only" @checked(old('type', request('event') ? 'expense' : 'expense') === 'income')> ລາຍຮັບ</label>
                            <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="type" value="expense" class="sr-only" @checked(old('type', 'expense') === 'expense')> ລາຍຈ່າຍ</label>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field name="date" label="ວັນທີ" required><input id="f-date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="input @error('date') input-error @enderror"></x-field>
                            <x-field name="amount" label="ຈຳນວນເງິນ (₭)" required>
                                <input id="f-amount" type="number" name="amount" min="1" step="1" inputmode="numeric" value="{{ old('amount') }}" class="input num @error('amount') input-error @enderror" data-money-preview="amount-preview">
                                <p id="amount-preview" class="hint"></p>
                            </x-field>
                        </div>
                        <x-field name="description" label="ລາຍລະອຽດ" required><input id="f-description" name="description" value="{{ old('description') }}" maxlength="255" class="input @error('description') input-error @enderror"></x-field>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field name="category" label="ໝວດ" required>
                                <div class="relative">
                                    <select name="category" id="f-category" class="input appearance-none pr-9 @error('category') input-error @enderror">
                                        @foreach (array_diff(\App\Support\Lookups::INCOME_CATEGORIES, [\App\Support\Lookups::DONATION_CATEGORY]) as $c)<option value="{{ $c }}" data-type="income" @selected(old('category') === $c)>{{ $c }}</option>@endforeach
                                        @foreach (\App\Support\Lookups::EXPENSE_CATEGORIES as $c)<option value="{{ $c }}" data-type="expense" @selected(old('category') === $c)>{{ $c }}</option>@endforeach
                                    </select>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-muted">{{ icon('chevron-down', 'size-4') }}</span>
                                </div>
                                <p class="hint">ລາຍຮັບບໍລິຈາກ ບັນທຶກຜ່ານໜ້າຕິດຕາມການບໍລິຈາກ</p>
                            </x-field>
                            <x-field name="event_id" label="ກິດຈະກຳ (ຖ້າມີ)"><x-select name="event_id" :options="$events->pluck('title', 'id')->all()" :selected="old('event_id', request('event'))" placeholder="— ບໍ່ມີ —" /></x-field>
                        </div>
                        <div class="rounded-xl border border-warning/30 bg-warning-bg p-3 text-sm text-warning" data-similar-box hidden>
                            <p class="font-semibold">{{ icon('triangle-alert', 'mr-1 inline size-4') }} ອາດຈະບັນທຶກຊ້ຳ — ມີທຸລະກຳຄ້າຍກັນໃນ 24 ຊົ່ວໂມງ:</p>
                            <ul class="mt-1 list-disc pl-5" data-similar-list></ul>
                            <p class="mt-1">ກວດແລ້ວ ກົດ “ບັນທຶກ” ຕໍ່ໄດ້.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6">
                        <button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button>
                        <button class="btn-primary">ບັນທຶກ</button>
                    </div>
                </form>
            </x-dialog>
        </x-slot:after>
    @endif
</x-layouts.app>
