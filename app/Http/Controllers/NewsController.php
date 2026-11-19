<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use App\Models\News;
use App\Models\NewsComment;
use App\Services\AuditLogger;
use App\Services\MediaService;
use App\Support\Lookups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** News (/news, /news/{id}) with comments, pinning, drafts and image galleries. */
class NewsController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index(Request $request): View
    {
        return $this->page($request, null);
    }

    public function show(Request $request, News $news): View
    {
        Gate::authorize('news.view', $news);

        return $this->page($request, $news);
    }

    private function page(Request $request, ?News $selected): View
    {
        $user = $request->user();
        $drafts = $request->boolean('drafts') && Gate::allows('news.manage');
        $q = trim((string) $request->query('q'));
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], ltrim($q, '#')).'%';

        $list = News::query()->visibleTo($user)
            ->when($drafts, fn ($w) => $w->where('status', 'draft'))
            ->when($request->filled('category'), fn ($w) => $w->where('category', $request->query('category')))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', $like)->orWhere('tags', 'like', $like)))
            ->withCount('comments')->with(['media' => fn ($m) => $m->limit(1)])
            ->feed()->paginate(10)->withQueryString();

        $selected ??= $list->first();
        if ($selected) {
            $selected->load(['author', 'media', 'comments.author.member']);
        }

        $editing = null;
        if ($request->filled('edit') && Gate::allows('news.manage')) {
            $editing = News::with('media')->find($request->integer('edit'));
        }

        return view('news.index', [
            'list' => $list,
            'selected' => $selected,
            'drafts' => $drafts,
            'draftCount' => Gate::allows('news.manage') ? News::where('status', 'draft')->count() : 0,
            'canManage' => Gate::allows('news.manage'),
            'editing' => $editing,
            'openForm' => Gate::allows('news.manage') && ($request->boolean('new') || $editing || (session('errors')?->any() && old('_form') === 'news')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('news.manage');
        $news = DB::transaction(function () use ($request) {
            $news = new News;
            $this->fill($news, $request);
            $news->author_id = $request->user()->id;
            $news->save();
            $this->saveImages($request, $news);

            return $news;
        });

        return redirect()->route('news.show', $news)->with('success', $news->status === 'published' ? 'ເຜີຍແຜ່ຂ່າວແລ້ວ' : 'ບັນທຶກສະບັບຮ່າງແລ້ວ');
    }

    public function update(Request $request, News $news): RedirectResponse
    {
        Gate::authorize('news.manage');
        DB::transaction(function () use ($request, $news) {
            $this->fill($news, $request);
            $news->save();
            $this->saveImages($request, $news);
        });

        return redirect()->route('news.show', $news)->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function destroy(News $news): RedirectResponse
    {
        Gate::authorize('news.manage');
        DB::transaction(function () use ($news) {
            AuditLogger::log('news_delete', $news, $news->title);
            foreach ($news->media()->get() as $m) {
                $this->media->delete($m->path);
                $m->delete();
            }
            $news->delete();
        });

        return redirect()->route('news.index')->with('success', 'ລຶບຂ່າວແລ້ວ');
    }

    public function pin(News $news): RedirectResponse
    {
        Gate::authorize('news.manage');
        $news->forceFill(['is_pinned' => ! $news->is_pinned])->save();

        return back()->with('success', $news->is_pinned ? 'ປັກໝຸດແລ້ວ' : 'ຖອນປັກໝຸດແລ້ວ');
    }

    public function uploadMedia(Request $request, News $news): RedirectResponse
    {
        Gate::authorize('news.manage');
        $this->saveImages($request, $news);

        return back()->with('success', 'ອັບໂຫຼດສຳເລັດ');
    }

    public function deleteMedia(News $news, MediaItem $mediaItem): RedirectResponse
    {
        Gate::authorize('news.manage');
        abort_unless($mediaItem->module === 'news' && $mediaItem->record_id === $news->id, 404);
        $this->media->delete($mediaItem->path);
        $mediaItem->delete();

        return back()->with('success', 'ລຶບຮູບແລ້ວ');
    }

    public function comment(Request $request, News $news): RedirectResponse
    {
        Gate::authorize('comments.create');
        Gate::authorize('news.view', $news);
        abort_unless($news->status === 'published', 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], ['body.required' => 'ກະລຸນາຂຽນຄຳເຫັນ']);
        $c = new NewsComment(['body' => trim($data['body'])]);
        $c->news_id = $news->id;
        $c->author_id = $request->user()->id;
        $c->save();

        return redirect()->to(route('news.show', $news).'#comments')->with('success', 'ສົ່ງຄຳເຫັນແລ້ວ');
    }

    public function updateComment(Request $request, NewsComment $comment): RedirectResponse
    {
        Gate::authorize('comments.update', $comment);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $comment->update(['body' => trim($data['body'])]);

        return redirect()->to(route('news.show', $comment->news_id).'#comments')->with('success', 'ແກ້ໄຂຄຳເຫັນແລ້ວ');
    }

    public function deleteComment(NewsComment $comment): RedirectResponse
    {
        Gate::authorize('comments.delete', $comment);
        $newsId = $comment->news_id;
        $comment->delete();

        return redirect()->to(route('news.show', $newsId).'#comments')->with('success', 'ລຶບຄຳເຫັນແລ້ວ');
    }

    private function fill(News $news, Request $request): void
    {
        $v = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'category' => ['required', Rule::in(Lookups::NEWS_CATEGORIES)],
            'tags' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'is_pinned' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm', 'max:51200'],
        ], ['title.required' => 'ກະລຸນາໃສ່ຫົວຂໍ້', 'body.required' => 'ກະລຸນາໃສ່ເນື້ອໃນ']);

        $news->fill([
            'title' => trim($v['title']),
            'body' => $v['body'],
            'category' => $v['category'],
            'tags' => collect(preg_split('/[,\s]+/', (string) ($v['tags'] ?? '')))->map(fn ($t) => ltrim(trim($t), '#'))->filter()->unique()->take(10)->values()->all(),
        ]);
        $news->is_pinned = (bool) ($v['is_pinned'] ?? false);
        if ($v['status'] === 'published' && $news->status !== 'published') {
            $news->published_at = now();
        }
        $news->status = $v['status'];
    }

    private function saveImages(Request $request, News $news): void
    {
        $order = (int) $news->media()->max('sort_order');
        foreach ($request->file('images', []) as $file) {
            $stored = $this->media->storeMedia($file, 'news/'.$news->id);
            MediaItem::create(['module' => 'news', 'record_id' => $news->id, ...$stored, 'sort_order' => ++$order, 'created_by' => $request->user()->id]);
        }
    }
}
