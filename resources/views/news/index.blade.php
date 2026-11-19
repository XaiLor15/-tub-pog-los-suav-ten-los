{{-- News — artboard "News" (list + article + comments) --}}
@php
    $s = $selected;
    $user = auth()->user();
    $chips = collect([['value' => '', 'label' => 'ທັງໝົດ']])->merge(collect(\App\Support\Lookups::NEWS_CATEGORIES)->map(fn ($c) => ['value' => $c, 'label' => $c]))->all();
@endphp
<x-layouts.app title="ຂ່າວສານ" :crumbs="['ໜ້າຫຼັກ', 'ຂ່າວສານ']">
    <x-page-header title="ຂ່າວສານ" description="ປະກາດ, ເລື່ອງລາວ ແລະ ຮູບພາບຂອງຕະກູນ">
        @if ($canManage)<x-slot:actions><a href="{{ route('news.index', ['new' => 1]) }}" class="btn-primary">{{ icon('pencil', 'size-4') }} ຂຽນຂ່າວ</a></x-slot:actions>
        @endif
    </x-page-header>

    <form method="GET" action="{{ route('news.index') }}" class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
        <input name="q" value="{{ request('q') }}" class="input lg:w-80" placeholder="ຄົ້ນຫາຫົວຂໍ້ ຫຼື Tag" aria-label="ຄົ້ນຫາຂ່າວ">
        <x-chips param="category" :options="$chips" :current="request('category', '')" label="ໝວດຂ່າວ" class="flex-1" />
        @if ($canManage)
            <a href="{{ route('news.index', $drafts ? [] : ['drafts' => 1]) }}" class="link text-sm">{{ $drafts ? 'ເບິ່ງທຸກຂ່າວ' : 'ສະບັບຮ່າງ ('.$draftCount.')' }}</a>
        @endif
    </form>

    <div class="grid items-start gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
        <div class="space-y-3 {{ request()->routeIs('news.show') ? 'order-2 lg:order-1' : '' }}" data-stagger>
            @forelse ($list as $n)
                @php $thumb = $n->media->first(); @endphp
                <a href="{{ route('news.show', array_merge(['news' => $n->id], request()->only(['q', 'category', 'drafts', 'page']))) }}"
                   class="card card-hover flex gap-3 p-3 {{ $s?->id === $n->id ? 'border-2 !border-primary' : '' }}">
                    <span class="flex h-[4.5rem] w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2 text-xs text-muted">
                        @if ($thumb && ! $thumb->isVideo())<img src="{{ $thumb->url() }}" alt="" loading="lazy" class="size-full object-cover">@else[ຮູບ]@endif
                    </span>
                    <span class="min-w-0">
                        <span class="flex flex-wrap items-center gap-2 text-xs text-muted">
                            <x-badge tone="primary" class="text-xs">{{ $n->category }}</x-badge>{{ $n->published_at ? lao_date($n->published_at) : '' }}
                            @if ($n->is_pinned)<span class="font-semibold text-warning">· ປັກໝຸດ</span>@endif
                            @if ($n->status === 'draft')<x-badge tone="warning" class="text-xs">ຮ່າງ</x-badge>@endif
                        </span>
                        <span class="mt-1 line-clamp-2 block font-bold">{{ $n->title }}</span>
                        <span class="text-xs text-muted">{{ $n->comments_count }} ຄຳເຫັນ</span>
                    </span>
                </a>
            @empty
                <div class="card"><x-empty icon="newspaper" :message="request()->hasAny(['q', 'category']) ? 'ບໍ່ພົບຂ່າວທີ່ກົງກັບການຄົ້ນຫາ' : 'ຍັງບໍ່ມີຂ່າວ'" /></div>
            @endforelse
            @if ($list->hasMorePages())<a href="{{ $list->nextPageUrl() }}" class="btn-outline w-full">ໂຫຼດເພີ່ມ</a>@endif
        </div>

        @if ($s)
            <article class="card overflow-hidden {{ request()->routeIs('news.show') ? 'order-1 lg:order-2' : '' }}">
                @php $media = $s->media; @endphp
                @if ($media->isNotEmpty())
                    <div class="relative aspect-video bg-surface-2">
                        <a href="{{ $media->first()->url() }}" data-lightbox="news-{{ $s->id }}" @if ($media->first()->isVideo()) data-video="1" @endif class="block size-full">
                            @if ($media->first()->isVideo())<span class="flex size-full items-center justify-center text-muted">▶ ວິດີໂອ</span>@else<img src="{{ $media->first()->url() }}" alt="{{ $s->title }}" class="size-full object-cover">@endif
                        </a>
                        @foreach ($media->skip(1) as $m)<a href="{{ $m->url() }}" data-lightbox="news-{{ $s->id }}" @if ($m->isVideo()) data-video="1" @endif hidden></a>@endforeach
                        <span class="absolute right-3 bottom-3 rounded-full bg-black/60 px-2.5 py-0.5 text-xs font-semibold text-white">1 / {{ $media->count() }}</span>
                    </div>
                @endif
                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-muted">
                        <x-badge tone="primary">{{ $s->category }}</x-badge>
                        <span>{{ $s->published_at ? lao_date($s->published_at) : 'ຍັງບໍ່ເຜີຍແຜ່' }}</span>
                        <span>· ໂດຍ {{ $s->author?->display_name ?? 'ຄະນະນຳຕະກູນ' }}</span>
                        @if ($s->status === 'draft')<x-badge tone="warning">ສະບັບຮ່າງ</x-badge>@endif
                    </div>
                    <h2 class="mt-3 text-[1.625rem] leading-snug">{{ $s->title }}</h2>
                    <div class="mt-3 text-[0.9375rem] whitespace-pre-line">{{ $s->body }}</div>
                    @if ($s->tags)
                        <div class="mt-4 flex flex-wrap gap-2">@foreach ($s->tags as $t)<a href="{{ route('news.index', ['q' => '#'.$t]) }}" class="rounded-full border border-border px-3 py-0.5 text-sm hover:bg-surface-2">#{{ $t }}</a>@endforeach</div>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" class="btn-outline btn-sm" data-copy="{{ route('news.show', $s) }}">{{ icon('external-link', 'size-4') }} ສຳເນົາລິ້ງ</button>
                        @if ($canManage)
                            <a href="{{ route('news.show', ['news' => $s->id, 'edit' => $s->id]) }}" class="btn-outline btn-sm">{{ icon('pencil', 'size-4') }} ແກ້ໄຂ</a>
                            <form method="POST" action="{{ route('news.pin', $s) }}">@csrf<button class="btn-outline btn-sm">{{ icon('pin', 'size-4') }} {{ $s->is_pinned ? 'ຖອນປັກໝຸດ' : 'ປັກໝຸດ' }}</button></form>
                            <form method="POST" action="{{ route('news.destroy', $s) }}" data-confirm="ລຶບຂ່າວ “{{ $s->title }}” ແລະ ຄຳເຫັນທັງໝົດ?" data-confirm-danger data-confirm-label="ລຶບ">@csrf @method('DELETE')<button class="btn-danger-outline btn-sm">{{ icon('trash-2', 'size-4') }} ລຶບ</button></form>
                        @endif
                    </div>

                    <section id="comments" class="mt-6 border-t border-border pt-5" aria-label="ຄຳເຫັນ">
                        <h3 class="font-bold">ຄຳເຫັນ ({{ $s->comments->count() }})</h3>
                        @if ($s->status === 'published')
                            @can('comments.create')
                                <form method="POST" action="{{ route('news.comments.store', $s) }}" class="mt-3 flex gap-3" data-busy>
                                    @csrf
                                    <x-avatar :name="$user->display_name" size="sm" class="!bg-primary !text-primary-foreground" />
                                    <div class="flex-1">
                                        <textarea name="body" rows="3" maxlength="2000" required class="textarea @error('body') input-error @enderror" placeholder="ຂຽນຄຳເຫັນ..." aria-label="ຂຽນຄຳເຫັນ">{{ old('body') }}</textarea>
                                        @error('body')<p class="error-text">{{ $message }}</p>@enderror
                                        <div class="mt-2 flex justify-end"><button class="btn-primary btn-sm">ສົ່ງ</button></div>
                                    </div>
                                </form>
                            @else
                                <p class="mt-3 rounded-lg bg-surface-2/70 p-3 text-sm text-muted">
                                    @guest<a href="{{ route('login', ['redirect' => '/news/'.$s->id]) }}" class="link">ເຂົ້າສູ່ລະບົບ</a> ເພື່ອຂຽນຄຳເຫັນ @else ບັນຊີທີ່ອະນຸມັດແລ້ວເທົ່ານັ້ນທີ່ຂຽນຄຳເຫັນໄດ້ @endguest
                                </p>
                            @endcan
                        @endif
                        <ul class="mt-4 space-y-4">
                            @forelse ($s->comments as $c)
                                <li class="flex gap-3">
                                    <x-avatar :name="$c->author?->member?->name ?? $c->author?->display_name" :sex="$c->author?->member?->sex" size="sm" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm"><span class="font-bold">{{ $c->author?->member?->name ?? $c->author?->display_name }}</span> <span class="text-muted">{{ \App\Support\Format::ago($c->created_at) }}{{ $c->updated_at->gt($c->created_at) ? ' · ແກ້ໄຂແລ້ວ' : '' }}</span></p>
                                        <p class="whitespace-pre-line">{{ $c->body }}</p>
                                        <div class="mt-1 flex gap-3 text-sm">
                                            @can('comments.update', $c)<button type="button" class="font-semibold text-primary" data-toggle="edit-c-{{ $c->id }}">ແກ້ໄຂ</button>@endcan
                                            @can('comments.delete', $c)
                                                <form method="POST" action="{{ route('comments.destroy', $c) }}" data-confirm="ລຶບຄຳເຫັນນີ້?" data-confirm-danger>@csrf @method('DELETE')<button class="font-semibold text-destructive">ລຶບ</button></form>
                                            @endcan
                                        </div>
                                        @can('comments.update', $c)
                                            <form id="edit-c-{{ $c->id }}" method="POST" action="{{ route('comments.update', $c) }}" class="mt-2 hidden">@csrf @method('PUT')
                                                <textarea name="body" rows="2" maxlength="2000" class="textarea" aria-label="ແກ້ໄຂຄຳເຫັນ">{{ $c->body }}</textarea>
                                                <div class="mt-2 flex justify-end"><button class="btn-primary btn-sm">ບັນທຶກ</button></div></form>
                                        @endcan
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-muted">ຍັງບໍ່ມີຄຳເຫັນ</li>
                            @endforelse
                        </ul>
                    </section>
                </div>
            </article>
        @endif
    </div>

    @if ($canManage)
        <x-slot:after>
            @php $ed = $editing; @endphp
            <x-dialog id="news-form" :title="$ed ? 'ແກ້ໄຂຂ່າວ' : 'ຂຽນຂ່າວ'" size="lg" :open="$openForm">
                <form method="POST" action="{{ $ed ? route('news.update', $ed) : route('news.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col" data-busy>
                    @csrf @if ($ed) @method('PUT') @endif <input type="hidden" name="_form" value="news">
                    <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                        <x-field name="title" label="ຫົວຂໍ້" required><input id="f-title" name="title" value="{{ old('title', $ed?->title) }}" maxlength="200" class="input @error('title') input-error @enderror"></x-field>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field name="category" label="ໝວດ" required><x-select name="category" :options="array_combine(\App\Support\Lookups::NEWS_CATEGORIES, \App\Support\Lookups::NEWS_CATEGORIES)" :selected="old('category', $ed?->category)" /></x-field>
                            <x-field name="tags" label="Tags" hint="ແຍກດ້ວຍຍະຫວ່າງ ຫຼື ຈຸດ, ເຊັ່ນ ກິນຈຽງ ປີໃໝ່"><input id="f-tags" name="tags" value="{{ old('tags', implode(' ', $ed?->tags ?? [])) }}" class="input"></x-field>
                        </div>
                        <x-field name="body" label="ເນື້ອໃນ" required><textarea id="f-body" name="body" rows="10" maxlength="20000" class="textarea @error('body') input-error @enderror">{{ old('body', $ed?->body) }}</textarea></x-field>
                        <x-field name="images" label="ຮູບ / ວິດີໂອ">
                            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" class="input py-2" data-preview="news-files" data-max-mb="50">
                            <p id="news-files" class="hint">ຮູບ ≤ 10 MB (ຍໍ່ 1920px) · ວິດີໂອ ≤ 50 MB</p>
                            @error('images.*')<p class="error-text">{{ $message }}</p>@enderror
                        </x-field>
                        @if ($ed && $ed->media->isNotEmpty())
                            <div class="grid grid-cols-4 gap-2">
                                @foreach ($ed->media as $m)
                                    <div class="relative aspect-square overflow-hidden rounded-lg bg-surface-2">
                                        @unless ($m->isVideo())<img src="{{ $m->url() }}" alt="" class="size-full object-cover">@endunless
                                        <button type="submit" form="del-media-{{ $m->id }}" class="icon-btn-sm absolute top-1 right-1 size-8 bg-card/90 text-destructive" aria-label="ລຶບຮູບ">{{ icon('x', 'size-4') }}</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="flex flex-wrap gap-6">
                            <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="is_pinned" value="1" class="size-4" @checked(old('is_pinned', $ed?->is_pinned))> ປັກໝຸດຂ່າວນີ້</label>
                            <div class="segmented">
                                <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="status" value="draft" class="sr-only" @checked(old('status', $ed?->status ?? 'draft') === 'draft')> ສະບັບຮ່າງ</label>
                                <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="status" value="published" class="sr-only" @checked(old('status', $ed?->status) === 'published')> ເຜີຍແຜ່</label>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6">
                        <button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button>
                        <button class="btn-primary">ບັນທຶກ</button>
                    </div>
                </form>
                @foreach ($ed?->media ?? [] as $m)
                    <form id="del-media-{{ $m->id }}" method="POST" action="{{ route('news.media.destroy', [$ed, $m]) }}" data-confirm="ລຶບຮູບນີ້?" data-confirm-danger hidden>@csrf @method('DELETE')</form>
                @endforeach
            </x-dialog>
        </x-slot:after>
    @endif
</x-layouts.app>
