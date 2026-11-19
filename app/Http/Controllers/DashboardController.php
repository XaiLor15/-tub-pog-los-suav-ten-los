<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\News;
use App\Services\FinanceService;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StatsService $stats, FinanceService $finance): View
    {
        $user = $request->user();
        $canFinance = (bool) $user?->isMember();

        $monthly = $canFinance ? $finance->monthly(6) : [];
        $thisMonth = $canFinance ? $finance->summary(now()->startOfMonth()->toDateString(), now()->toDateString()) : null;

        return view('dashboard', [
            'stats' => $stats->dashboard(),
            'breakdown' => $stats->breakdown(),
            'canFinance' => $canFinance,
            'balance' => $canFinance ? $finance->summary()['balance'] : null,
            'thisMonth' => $thisMonth,
            'monthly' => $monthly,
            'events' => Event::query()->visibleTo($user)->upcoming()->withCount('participants')->limit(5)->get(),
            'news' => News::query()->published()->feed()->limit(5)->get(),
        ]);
    }
}
