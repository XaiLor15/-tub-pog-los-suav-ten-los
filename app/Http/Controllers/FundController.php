<?php

namespace App\Http\Controllers;

use App\Models\FundMember;
use App\Services\AuditLogger;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Fund contributors (/fund-contributors): v_fund_contributor_totals + v_fund_by_province. */
class FundController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('fund.view');
        $all = $this->totals();
        $province = $request->query('province');
        $q = mb_strtolower(trim((string) $request->query('q')));

        $byProvince = $all->groupBy(fn ($r) => $r->province ?: 'ບໍ່ລະບຸ')
            ->map(fn ($g, $p) => ['province' => $p, 'contributors' => $g->count(), 'total' => (int) $g->sum('total_donated')])
            ->sortByDesc('total')->values();

        $rows = $all->when($province, fn ($c) => $c->filter(fn ($r) => ($r->province ?: 'ບໍ່ລະບຸ') === $province))
            ->when($q !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r->name.' '.$r->village), $q)))
            ->sortByDesc('total_donated')->values();

        $total = (int) $all->sum('total_donated');

        return view('fund.index', [
            'rows' => $rows,
            'byProvince' => $byProvince,
            'maxProvince' => max(1, (int) $byProvince->max('total')),
            'count' => $all->count(),
            'newThisMonth' => $all->filter(fn ($r) => $r->joined_at >= now()->startOfMonth()->toDateString())->count(),
            'total' => $total,
            'since' => $all->min('joined_at') ? substr($all->min('joined_at'), 0, 4) : null,
            'average' => $all->count() ? (int) round($total / $all->count()) : 0,
            'provinces' => $all->pluck('province')->filter()->unique()->count(),
            'villages' => $all->pluck('village')->filter()->unique()->count(),
            'province' => $province,
            'canManage' => Gate::allows('fund.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('fund.manage');
        $v = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at'), Rule::unique('fund_members', 'member_id')],
            'joined_at' => ['required', 'date', 'before_or_equal:today'],
        ], ['member_id.unique' => 'ເປັນຜູ້ຮ່ວມແລ້ວ', 'member_id.required' => 'ກະລຸນາເລືອກສະມາຊິກ']);
        $f = new FundMember($v);
        $f->created_by = $request->user()->id;
        $f->save();
        AuditLogger::log('fund_add', $f, $f->member->name);

        return back()->with('success', 'ເພີ່ມຜູ້ຮ່ວມກອງທຶນແລ້ວ');
    }

    public function destroy(FundMember $fundMember): RedirectResponse
    {
        Gate::authorize('fund.manage');
        AuditLogger::log('fund_remove', $fundMember, $fundMember->member?->name);
        $fundMember->delete();

        return back()->with('success', 'ລຶບຜູ້ຮ່ວມກອງທຶນແລ້ວ (ປະຫວັດການບໍລິຈາກຍັງຢູ່)');
    }

    public function export(): StreamedResponse
    {
        Gate::authorize('fund.view');
        $rows = $this->totals()->sortByDesc('total_donated')->map(fn ($r) => [
            $r->name, $r->province, $r->village, (int) $r->total_donated, (int) $r->times, substr((string) $r->joined_at, 0, 4),
        ]);

        return Csv::download('fund-contributors', ['ຊື່', 'ແຂວງ', 'ບ້ານ', 'ຍອດບໍລິຈາກລວມ (₭)', 'ຄັ້ງ', 'ເຂົ້າຮ່ວມ'], $rows);
    }

    /** v_fund_contributor_totals: member_id, name, province, village, total_donated, times, joined_at. */
    private function totals(): Collection
    {
        $paid = DB::table('donation_payments')
            ->join('donation_pledges', 'donation_pledges.id', '=', 'donation_payments.pledge_id')
            ->selectRaw('donation_pledges.member_id, SUM(donation_payments.amount) as total, COUNT(*) as times')
            ->groupBy('donation_pledges.member_id');

        return DB::table('fund_members')
            ->join('members', 'members.id', '=', 'fund_members.member_id')
            ->leftJoinSub($paid, 'p', 'p.member_id', '=', 'fund_members.member_id')
            ->select('fund_members.id', 'fund_members.member_id', 'fund_members.joined_at', 'members.name', 'members.sex', 'members.province', 'members.village')
            ->selectRaw('COALESCE(p.total, 0) as total_donated, COALESCE(p.times, 0) as times')
            ->get();
    }
}
