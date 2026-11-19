<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Member;
use App\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** global_search(q): members (Lao + Hmong names), pages, events, quick commands — filtered by role. */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim(mb_substr((string) $request->query('q'), 0, 100));
        $user = $request->user();
        $private = Gate::allows('view-private');
        if ($q === '') {
            return response()->json(['members' => [], 'pages' => [], 'events' => [], 'commands' => []]);
        }
        $needle = mb_strtolower($q);

        $members = Member::search($q)->orderBy('generation')->limit(6)->get()->map(fn (Member $m) => [
            'title' => $m->name.($m->hmong_name ? ' ('.$m->hmong_name.')' : ''),
            'subtitle' => collect(['Phaj '.$m->generation, $m->province, $private && $m->phone ? Format::phone($m->phone) : null])->filter()->implode(' · '),
            'url' => route('members.show', $m),
            'initial' => Format::initial($m->name),
            'sex' => $m->sex,
        ]);

        $pages = collect([
            ['ໜ້າຫຼັກ', 'dashboard home', route('dashboard'), true],
            ['ສະມາຊິກ', 'members', route('members.index'), true],
            ['ແຜນຜັງຕະກູນ', 'family tree phaj', route('members.index', ['view' => 'tree']), true],
            ['ຄະນະນຳ', 'leadership', route('leadership.index'), true],
            ['ຜູ້ອາວຸໂສ', 'elders', route('elders.index'), true],
            ['ກິດຈະກຳ', 'activities events', route('activities.index'), true],
            ['ຂ່າວສານ', 'news', route('news.index'), true],
            ['ການເງິນ', 'finance', route('finance.index'), (bool) $user?->isMember()],
            ['ຜູ້ຮ່ວມກອງທຶນ', 'fund contributors', route('fund.index'), (bool) $user?->isMember()],
            ['ຕິດຕາມການບໍລິຈາກ', 'donation tracking', route('donations.index'), (bool) $user?->isMember()],
            ['ຈັດການຜູ້ໃຊ້', 'admin users audit', route('admin.index'), (bool) $user?->isAdmin()],
            ['ໂປຣໄຟລ໌', 'profile', route('profile'), (bool) $user],
        ])->filter(fn ($p) => $p[3] && (str_contains(mb_strtolower($p[0]), $needle) || str_contains($p[1], $needle)))
            ->map(fn ($p) => ['title' => $p[0], 'subtitle' => 'ໜ້າ', 'url' => $p[2], 'glyph' => '→'])->values();

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $events = Event::query()->visibleTo($user)->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('location', 'like', $like))
            ->orderByDesc('start_date')->limit(4)->get()->map(fn (Event $e) => [
                'title' => $e->title, 'subtitle' => Format::date($e->start_date).($e->location ? ' · '.$e->location : ''),
                'url' => route('activities.show', $e), 'glyph' => (string) $e->start_date->day,
            ]);

        $commands = collect([
            ['ປ່ຽນເປັນໂໝດມືດ / ແຈ້ງ', 'ການສະແດງຜົນ', null, 'theme', true, 'dark theme mode ມືດ ແຈ້ງ ໂໝດ'],
            ['ເພີ່ມສະມາຊິກ', 'ສະມາຊິກ', route('members.index', ['new' => 1]), null, Gate::allows('members.write'), 'add member ເພີ່ມ'],
            ['ສ້າງກິດຈະກຳ', 'ກິດຈະກຳ', route('activities.index', ['new' => 1]), null, Gate::allows('events.create'), 'new event ເພີ່ມ ສ້າງ'],
            ['ບັນທຶກທຸລະກຳ', 'ການເງິນ', route('finance.index', ['new' => 1]), null, Gate::allows('finance.manage'), 'transaction ເພີ່ມ ບັນທຶກ'],
        ])->filter(fn ($c) => $c[4] && (str_contains(mb_strtolower($c[0]), $needle) || str_contains($c[5], $needle)))
            ->map(fn ($c) => ['title' => $c[0], 'subtitle' => $c[1], 'url' => $c[2], 'action' => $c[3], 'glyph' => '+'])->values();

        return response()->json(['members' => $members, 'pages' => $pages, 'events' => $events, 'commands' => $commands]);
    }
}
