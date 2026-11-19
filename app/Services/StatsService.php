<?php

namespace App\Services;

use App\Models\Elder;
use App\Models\Event;
use App\Models\Leadership;
use App\Models\Member;
use App\Models\News;
use Illuminate\Support\Facades\DB;

/** v_dashboard_stats + v_member_breakdown. Counts only, safe for every role. */
class StatsService
{
    /** @return array<string, int|null> */
    public function dashboard(): array
    {
        $year = now()->year;
        $term = Leadership::query()->selectRaw('MIN(term_start) as s, MAX(term_end) as e')->first();

        return [
            'members' => Member::count(),
            'members_added_30d' => Member::where('created_at', '>=', now()->subDays(30))->count(),
            'leaders' => Leadership::count(),
            'term_start' => $term?->s ? (int) substr($term->s, 0, 4) : null,
            'term_end' => $term?->e ? (int) substr($term->e, 0, 4) : null,
            'elders' => Elder::count(),
            'elders_rite' => Elder::where('role_category', 'rite')->count(),
            'generations' => Member::distinct()->count('generation'),
            'min_generation' => Member::min('generation'),
            'max_generation' => Member::max('generation'),
            'events_this_year' => Event::whereYear('start_date', $year)->count(),
            'upcoming_events' => Event::query()->upcoming()->count(),
            'news_total' => News::published()->count(),
            'news_added_7d' => News::published()->where('published_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * @return array{by_generation: array<int,int>, male:int, female:int, alive:int, dead:int, total:int}
     */
    public function breakdown(): array
    {
        $rows = Member::query()->select('generation', 'sex', 'is_alive', DB::raw('count(*) as c'))
            ->groupBy('generation', 'sex', 'is_alive')->get();

        $byGen = $rows->groupBy('generation')->map(fn ($g) => (int) $g->sum('c'))->sortKeys()->all();

        return [
            'by_generation' => $byGen,
            'male' => (int) $rows->where('sex', 'male')->sum('c'),
            'female' => (int) $rows->where('sex', 'female')->sum('c'),
            'alive' => (int) $rows->filter(fn ($r) => (bool) $r->is_alive)->sum('c'),
            'dead' => (int) $rows->filter(fn ($r) => ! $r->is_alive)->sum('c'),
            'total' => (int) $rows->sum('c'),
        ];
    }
}
