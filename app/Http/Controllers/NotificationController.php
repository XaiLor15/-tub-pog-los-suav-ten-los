<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\News;
use App\Models\NotificationRead;
use App\Models\User;
use App\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** get_notifications(): events in the next 7 days, news from the last 7 days, pending users (Admin). */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->items($request->user()));
    }

    public function read(Request $request): JsonResponse
    {
        $user = $request->user();
        $keys = $request->boolean('all')
            ? collect($this->items($user)['items'])->pluck('key')->all()
            : [(string) $request->validate(['key' => ['required', 'string', 'max:64', 'regex:/^(event|news):\d+$|^pending_users:\d+$/']])['key']];

        $now = now();
        DB::table('notification_reads')->upsert(
            array_map(fn ($k) => ['user_id' => $user->id, 'item_key' => $k, 'read_at' => $now], $keys),
            ['user_id', 'item_key'], ['read_at'],
        );

        return response()->json(['ok' => true]);
    }

    /** @return array{items: array<int, array<string, mixed>>, unread: int} */
    private function items(User $user): array
    {
        $items = [];

        if ($user->isAdmin()) {
            $pending = User::where('role', 'pending')->whereNull('rejected_at')->where('is_disabled', false)->latest()->get(['display_name', 'created_at']);
            if ($pending->isNotEmpty()) {
                $names = $pending->take(2)->pluck('display_name')->implode(', ').($pending->count() > 2 ? ' ແລະ ອີກ '.($pending->count() - 2).' ຄົນ' : '');
                // Key includes the count so a new registration shows as unread again.
                $items[] = ['key' => 'pending_users:'.$pending->count(), 'kind' => 'user', 'glyph' => '+', 'group' => 'today',
                    'title' => 'ມີຜູ້ໃຊ້ໃໝ່ '.$pending->count().' ຄົນ ລໍຖ້າອະນຸມັດ', 'subtitle' => $names.' · '.Format::ago($pending->first()->created_at),
                    'url' => route('admin.index'), 'at' => $pending->first()->created_at->timestamp];
            }
        }

        $events = Event::query()->visibleTo($user)
            ->whereDate('start_date', '>=', today())->whereDate('start_date', '<=', today()->addDays(7))
            ->orderBy('start_date')->get();
        foreach ($events as $e) {
            $days = (int) today()->diffInDays($e->start_date);
            $items[] = ['key' => 'event:'.$e->id, 'kind' => 'event', 'glyph' => '▦', 'group' => $days === 0 ? 'today' : 'upcoming',
                'title' => $e->title.($days === 0 ? ' ມື້ນີ້' : ' ໃນອີກ '.$days.' ວັນ'),
                'subtitle' => Format::weekday($e->start_date).' '.Format::date($e->start_date).($e->start_time ? ' · '.substr($e->start_time, 0, 5) : '').($e->location ? ' · '.$e->location : ''),
                'url' => route('activities.show', $e), 'at' => $e->start_date->timestamp];
        }

        $news = News::published()->where('published_at', '>=', now()->subDays(7))->withCount('comments')->orderByDesc('published_at')->get();
        foreach ($news as $n) {
            $items[] = ['key' => 'news:'.$n->id, 'kind' => 'news', 'glyph' => 'ຂ', 'group' => $n->published_at->isToday() ? 'today' : 'week',
                'title' => 'ຂ່າວໃໝ່: '.$n->title, 'subtitle' => $n->category.' · '.Format::date($n->published_at).' · '.$n->comments_count.' ຄຳເຫັນ',
                'url' => route('news.show', $n), 'at' => $n->published_at->timestamp];
        }

        $read = NotificationRead::where('user_id', $user->id)->whereIn('item_key', array_column($items, 'key'))->pluck('item_key')->flip();
        foreach ($items as &$item) {
            $item['read'] = isset($read[$item['key']]);
        }
        unset($item);

        return ['items' => $items, 'unread' => count(array_filter($items, fn ($i) => ! $i['read']))];
    }
}
