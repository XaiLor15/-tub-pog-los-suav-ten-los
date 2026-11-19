{{-- Fund contributors — artboard "FundContributors" --}}
<x-layouts.app title="ຜູ້ຮ່ວມກອງທຶນ" :crumbs="['ກອງທຶນ', 'ຜູ້ຮ່ວມກອງທຶນ']">
    <x-page-header title="ຜູ້ຮ່ວມກອງທຶນ" icon="heart-handshake" description="ສະມາຊິກທີ່ເຂົ້າຮ່ວມກອງທຶນຕະກູນ ແລະ ຍອດບໍລິຈາກສະສົມ">
        <x-slot:actions>
            <a href="{{ route('fund.export') }}" class="btn-outline">{{ icon('file-down', 'size-4') }} Export CSV</a>
            @if ($canManage)<button type="button" class="btn-primary" data-modal-open="fund-form">{{ icon('plus', 'size-4') }} ເພີ່ມຜູ້ຮ່ວມ</button>@endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" data-stagger>
        <x-stat-card label="ຜູ້ຮ່ວມທັງໝົດ" icon="users" :value="$count.' ຄົນ'" :hint="'+'.$newThisMonth.' ໃນເດືອນນີ້'" :hint-tone="$newThisMonth ? 'success' : null" />
        <x-stat-card label="ຍອດບໍລິຈາກສະສົມ" icon="piggy-bank" :value="money($total)" tone="success" :hint="$since ? 'ຕັ້ງແຕ່ປີ '.$since : '—'" />
        <x-stat-card label="ສະເລ່ຍຕໍ່ຄົນ" icon="scale" :value="money($average)" hint="ຈາກລາຍການບໍລິຈາກທັງໝົດ" />
        <x-stat-card label="ພື້ນທີ່" icon="map-pin" :value="$provinces.' ແຂວງ'" :hint="$villages.' ບ້ານ'" />
    </div>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
        <x-panel id="by-prov" title="ສະຖິຕິຕາມແຂວງ" description="ກົດແຂວງເພື່ອກັ່ນຕອງລາຍຊື່" icon="map-pin">
            <div class="space-y-2" data-bars>
                @forelse ($byProvince as $p)
                    @php $active = $province === $p['province']; @endphp
                    <a href="{{ route('fund.index', $active ? [] : ['province' => $p['province']]) }}" class="block rounded-lg p-2 transition-colors {{ $active ? 'bg-primary-soft ring-1 ring-primary/30' : 'hover:bg-surface-2/60' }}" @if ($active) aria-current="true" @endif>
                        <span class="flex items-center justify-between gap-2 text-sm"><span class="font-semibold">{{ $p['province'] }}</span><span class="num text-muted">{{ $p['contributors'] }} ຄົນ · {{ money($p['total']) }}</span></span>
                        <span class="mt-1.5 block h-2 overflow-hidden rounded-full bg-surface-2"><span class="bar-grow block h-full rounded-full bg-chart-1" style="width: {{ round($p['total'] / $maxProvince * 100, 1) }}%"></span></span>
                    </a>
                @empty
                    <p class="text-sm text-muted">ຍັງບໍ່ມີຂໍ້ມູນ</p>
                @endforelse
            </div>
        </x-panel>

        <div class="card overflow-hidden">
            <form method="GET" class="toolbar border-b border-border lg:justify-between">
                @if ($province)<input type="hidden" name="province" value="{{ $province }}">@endif
                <x-search placeholder="ຄົ້ນຫາຊື່ຜູ້ຮ່ວມ" class="lg:w-80" />
                @if ($province)<a href="{{ route('fund.index') }}" class="chip" aria-pressed="true">{{ icon('map-pin', 'size-4') }} {{ $province }} {{ icon('x', 'size-3.5') }}</a>@endif
            </form>
            @if ($rows->isEmpty())
                <x-empty icon="heart-handshake" :message="$count ? 'ບໍ່ພົບຜູ້ຮ່ວມທີ່ກົງກັບຕົວກອງ' : 'ຍັງບໍ່ມີຜູ້ຮ່ວມກອງທຶນ'" />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead><tr><th>ຊື່</th><th>ແຂວງ / ບ້ານ</th><th class="text-right">ຍອດບໍລິຈາກລວມ</th><th class="text-right">ຄັ້ງ</th><th>ເຂົ້າຮ່ວມ</th>@if ($canManage)<th></th>@endif</tr></thead>
                        <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td><a href="{{ route('members.show', $r->member_id) }}" class="flex items-center gap-3"><x-avatar :name="$r->name" :sex="$r->sex" size="sm" /><span class="font-semibold">{{ $r->name }}</span></a></td>
                                <td class="text-sm">{{ collect([$r->province, $r->village])->filter()->implode(' · ') ?: '—' }}</td>
                                <td class="num text-right font-semibold whitespace-nowrap">{{ money($r->total_donated) }}</td>
                                <td class="num text-right">{{ $r->times }}</td>
                                <td class="num">{{ substr($r->joined_at, 0, 4) }}</td>
                                @if ($canManage)
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('fund.destroy', $r->id) }}" data-confirm="ລຶບ {{ $r->name }} ອອກຈາກຜູ້ຮ່ວມກອງທຶນ? ປະຫວັດການບໍລິຈາກຍັງຢູ່." data-confirm-danger data-confirm-label="ລຶບ">@csrf @method('DELETE')
                                            <button class="icon-btn-sm border-0 text-destructive" aria-label="ລຶບ {{ $r->name }}">{{ icon('trash-2', 'size-4') }}</button></form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if ($canManage)
        <x-slot:after>
            <x-dialog id="fund-form" title="ເພີ່ມຜູ້ຮ່ວມກອງທຶນ" size="sm" :open="$errors->any() && old('_form') === 'fund'">
                <form method="POST" action="{{ route('fund.store') }}" class="flex min-h-0 flex-1 flex-col" data-busy>
                    @csrf <input type="hidden" name="_form" value="fund">
                    <div class="flex-1 space-y-4 p-5 sm:p-6">
                        <x-field name="member_id" label="ສະມາຊິກ" required hint="ເລືອກຈາກລາຍຊື່ເທົ່ານັ້ນ · 1 ຄົນ 1 ຄັ້ງ">
                            <div data-member-picker><input type="hidden" name="member_id" data-picker-value><input class="input @error('member_id') input-error @enderror" placeholder="ພິມຊື່..." data-picker-input></div>
                        </x-field>
                        <x-field name="joined_at" label="ວັນທີເຂົ້າຮ່ວມ" required><input id="f-joined_at" type="date" name="joined_at" value="{{ old('joined_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="input"></x-field>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6"><button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button><button class="btn-primary">ເພີ່ມ</button></div>
                </form>
            </x-dialog>
        </x-slot:after>
    @endif
</x-layouts.app>
