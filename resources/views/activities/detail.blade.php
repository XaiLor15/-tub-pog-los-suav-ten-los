@php $tabUrl = fn ($t) => route('activities.show', array_merge(['event' => $e->id, 'tab' => $t], request()->only(['q', 'category', 'when']))); @endphp
<article class="card overflow-hidden">
    <div class="lattice relative flex h-44 items-end bg-brand-navy p-6 sm:h-48">
        @if ($e->coverUrl())<img src="{{ $e->coverUrl() }}" alt="" class="absolute inset-0 size-full object-cover">@endif
        <span class="relative text-sm text-white/80">@unless ($e->coverUrl())[ຮູບໜ້າປົກກິດຈະກຳ]@endunless</span>
    </div>
    <div class="p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap gap-2"><x-badge tone="primary">{{ $e->category }}</x-badge><x-badge :tone="$e->is_public ? 'success' : 'neutral'">{{ $e->is_public ? 'ສາທາລະນະ' : 'ສະເພາະສະມາຊິກ' }}</x-badge></div>
                <h2 class="mt-2 text-[1.625rem]">{{ $e->title }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($canEdit)<a href="{{ route('activities.show', ['event' => $e->id, 'edit' => $e->id]) }}" class="btn-outline">ແກ້ໄຂ</a>@endif
                @if ($canJoin)
                    @if ($joined)
                        <form method="POST" action="{{ route('activities.leave', $e) }}" data-confirm="ຍົກເລີກການເຂົ້າຮ່ວມ {{ $e->title }}?">@csrf @method('DELETE')<button class="btn-outline">{{ icon('check', 'size-4') }} ລົງຊື່ແລ້ວ</button></form>
                    @else
                        <form method="POST" action="{{ route('activities.join', $e) }}">@csrf<button class="btn-primary">ລົງຊື່ເຂົ້າຮ່ວມ</button></form>
                    @endif
                @elseif (! auth()->check())
                    <a href="{{ route('login', ['redirect' => '/activities/'.$e->id]) }}" class="btn-primary">ເຂົ້າສູ່ລະບົບເພື່ອລົງຊື່</a>
                @endif
            </div>
        </div>

        <nav class="mt-5 flex gap-1 overflow-x-auto border-b border-border" aria-label="ແຖບກິດຈະກຳ">
            @foreach ($tabs as $key => $label)
                <a href="{{ $tabUrl($key) }}" class="min-h-11 border-b-2 px-3 py-2.5 font-semibold whitespace-nowrap {{ $tab === $key ? 'border-primary text-foreground' : 'border-transparent text-muted hover:text-foreground' }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="pt-5">
            @if ($tab === 'overview')
                @can('events.view-all-tabs')
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="rounded-xl bg-surface-2/70 p-4"><p class="text-sm text-muted">ຜູ້ເຂົ້າຮ່ວມ</p><p class="num text-2xl font-bold">{{ $summary['participants'] }}</p></div>
                        <div class="rounded-xl bg-surface-2/70 p-4"><p class="text-sm text-muted">ຍອດບໍລິຈາກ</p><p class="num text-xl font-bold text-success">{{ money($summary['donations']) }}</p></div>
                        <div class="rounded-xl bg-surface-2/70 p-4"><p class="text-sm text-muted">ລາຍຈ່າຍ</p><p class="num text-xl font-bold text-destructive">{{ money($summary['expenses']) }}</p></div>
                        <div class="rounded-xl bg-primary-soft p-4"><p class="text-sm text-muted">ຍອດສຸດທິ</p><p class="num text-xl font-bold text-primary">{{ money($summary['net']) }}</p></div>
                    </div>
                @endcan
                <dl class="mt-5 grid grid-cols-[7rem_1fr] gap-y-3">
                    <dt class="text-muted">ວັນທີ</dt><dd>{{ $e->dateRange() }}</dd>
                    <dt class="text-muted">ເວລາ</dt><dd>{{ $e->timeRange() }}</dd>
                    <dt class="text-muted">ສະຖານທີ່</dt><dd>{{ $e->location ?? '—' }}</dd>
                    <dt class="text-muted">ຜູ້ຈັດ</dt><dd>{{ $e->organizer ?? '—' }}</dd>
                </dl>
                @if ($e->description)<p class="mt-4 whitespace-pre-line">{{ $e->description }}</p>@endif
                @if ($canEdit && auth()->user()?->isAdmin())
                    <form method="POST" action="{{ route('activities.destroy', $e) }}" class="mt-6" data-confirm="ລຶບກິດຈະກຳ {{ $e->title }}?" data-confirm-danger data-confirm-label="ລຶບ">@csrf @method('DELETE')
                        <button class="btn-danger-outline btn-sm">{{ icon('trash-2', 'size-4') }} ລຶບກິດຈະກຳ</button></form>
                @endif

            @elseif ($tab === 'participants')
                @if ($canEdit)
                    <form method="POST" action="{{ route('activities.participants.store', $e) }}" class="mb-5 grid gap-3 rounded-xl border border-border p-4 sm:grid-cols-[1fr_10rem_9rem_auto] sm:items-end">
                        @csrf
                        <div><label class="label">ສະມາຊິກ</label><div data-member-picker><input type="hidden" name="member_id" data-picker-value><input class="input @error('member_id') input-error @enderror" placeholder="ພິມຊື່..." data-picker-input></div>@error('member_id')<p class="error-text">{{ $message }}</p>@enderror</div>
                        <div><label for="p-duty" class="label">ໜ້າທີ່</label><input id="p-duty" name="duty" class="input" maxlength="100" placeholder="ເຊັ່ນ ຝ່າຍອາຫານ"></div>
                        <div><label class="label" for="f-status">ສະຖານະ</label><x-select name="status" :options="\App\Support\Lookups::PARTICIPANT_STATUS" selected="confirmed" /></div>
                        <button class="btn-primary">ເພີ່ມ</button>
                    </form>
                @endif
                @forelse ($participants as $p)
                    <div class="flex items-center gap-3 border-b border-border py-2.5 last:border-0">
                        <x-avatar :member="$p->member" size="sm" />
                        <span class="min-w-0 flex-1"><a href="{{ route('members.show', $p->member_id) }}" class="font-semibold hover:underline">{{ $p->member?->name }}</a><span class="block text-sm text-muted">{{ $p->duty ?: 'ຜູ້ເຂົ້າຮ່ວມ' }}</span></span>
                        <x-badge :tone="$p->status === 'confirmed' ? 'success' : 'warning'">{{ \App\Support\Lookups::PARTICIPANT_STATUS[$p->status] }}</x-badge>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('activities.participants.destroy', [$e, $p]) }}" data-confirm="ເອົາ {{ $p->member?->name }} ອອກຈາກກິດຈະກຳ?" data-confirm-danger>@csrf @method('DELETE')
                                <button class="icon-btn-sm text-destructive" aria-label="ເອົາອອກ">{{ icon('x', 'size-4') }}</button></form>
                        @endif
                    </div>
                @empty
                    <x-empty icon="users" message="ຍັງບໍ່ມີຜູ້ເຂົ້າຮ່ວມ" />
                @endforelse

            @elseif ($tab === 'donations')
                <div class="mb-3 flex justify-end"><a href="{{ route('donations.index', ['event' => $e->id]) }}" class="link text-sm">ໄປໜ້າຕິດຕາມການບໍລິຈາກ →</a></div>
                @forelse ($progress as $r)
                    @php [$label, $tone] = \App\Services\FinanceService::statusLabel($r['status']); @endphp
                    <div class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1 border-b border-border py-2.5 last:border-0 sm:grid-cols-[10rem_1fr_8rem_6rem]">
                        <span class="font-semibold">{{ $r['member']?->name }}</span>
                        <x-progress :value="$r['pct']" class="hidden sm:block" />
                        <span class="num text-right text-sm">{{ money($r['paid']) }}{{ $r['expected'] ? ' / '.money($r['expected']) : '' }}</span>
                        <x-badge :tone="$tone" class="justify-self-end">{{ $label }}</x-badge>
                    </div>
                @empty
                    <x-empty icon="hand-coins" message="ຍັງບໍ່ມີລາຍການບໍລິຈາກສຳລັບກິດຈະກຳນີ້" />
                @endforelse

            @elseif ($tab === 'expenses')
                @forelse ($expenses as $t)
                    <div class="flex items-center justify-between gap-4 border-b border-border py-2.5 last:border-0">
                        <span><span class="block font-semibold">{{ $t->description }}</span><span class="text-sm text-muted">{{ lao_date($t->date) }} · {{ $t->category }}</span></span>
                        <x-money :amount="$t->amount" sign="-" class="font-semibold" />
                    </div>
                @empty
                    <x-empty icon="wallet" message="ຍັງບໍ່ມີລາຍຈ່າຍສຳລັບກິດຈະກຳນີ້" />
                @endforelse
                @can('finance.manage')<a href="{{ route('finance.index', ['new' => 1, 'event' => $e->id]) }}" class="btn-outline btn-sm mt-4">+ ບັນທຶກລາຍຈ່າຍ</a>@endcan

            @elseif ($tab === 'photos')
                @if ($canEdit)
                    <form method="POST" action="{{ route('activities.media.store', $e) }}" enctype="multipart/form-data" class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border-2 border-dashed border-input p-4" data-busy>
                        @csrf
                        <input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" class="flex-1 text-sm" data-preview="ev-files" data-max-mb="50" aria-label="ເລືອກຮູບ ຫຼື ວິດີໂອ">
                        <button class="btn-primary btn-sm" data-busy-label="ກຳລັງອັບໂຫຼດ...">{{ icon('upload', 'size-4') }} ອັບໂຫຼດ</button>
                        <p id="ev-files" class="hint w-full">ຮູບ JPG/PNG/WebP ≤ 10 MB · ວິດີໂອ MP4/WebM ≤ 50 MB</p>
                        @error('files')<p class="error-text w-full">{{ $message }}</p>@enderror @error('files.*')<p class="error-text w-full">{{ $message }}</p>@enderror
                    </form>
                @endif
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @forelse ($media as $m)
                        <div class="group relative aspect-square overflow-hidden rounded-xl bg-surface-2">
                            <a href="{{ $m->url() }}" data-lightbox="event-{{ $e->id }}" @if ($m->isVideo()) data-video="1" @endif class="block size-full">
                                @if ($m->isVideo())<span class="flex size-full items-center justify-center text-muted">▶ ວິດີໂອ</span>@else<img src="{{ $m->url() }}" alt="" loading="lazy" class="size-full object-cover">@endif
                            </a>
                            @if ($canEdit)
                                <form method="POST" action="{{ route('activities.media.destroy', [$e, $m]) }}" class="absolute top-2 right-2" data-confirm="ລຶບຮູບນີ້?" data-confirm-danger>@csrf @method('DELETE')
                                    <button class="icon-btn-sm bg-card/90 text-destructive" aria-label="ລຶບຮູບ">{{ icon('trash-2', 'size-4') }}</button></form>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full"><x-empty icon="image" message="ຍັງບໍ່ມີຮູບພາບ" /></div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</article>
