<?php

namespace App\Http\Controllers;

use App\Models\DonationPayment;
use App\Models\DonationPledge;
use App\Models\Event;
use App\Services\AuditLogger;
use App\Services\FinanceService;
use App\Services\MediaService;
use App\Support\Csv;
use App\Support\Format;
use App\Support\Lookups;
use App\Support\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Donation tracking (/donation-tracking?event=id): pledges, payments, progress. Member = read, Admin = write. */
class DonationController extends Controller
{
    public function __construct(private FinanceService $finance, private MediaService $media) {}

    public function index(Request $request): View
    {
        Gate::authorize('donations.view');
        $events = Event::orderByDesc('start_date')->get(['id', 'title', 'start_date']);
        $event = $request->filled('event') ? Event::find($request->integer('event')) : null;
        $event ??= Event::whereHas('pledges')->upcoming()->first() ?? Event::whereHas('pledges')->orderByDesc('start_date')->first() ?? $events->first();

        $progress = $event ? $this->finance->donationProgress($event) : collect();
        $totals = $this->finance->eventTotals($progress);
        $status = in_array($request->query('status'), ['unpaid', 'partial', 'paid', 'over'], true) ? $request->query('status') : '';
        $q = mb_strtolower(trim((string) $request->query('q')));
        $rows = $progress
            ->when($status, fn ($c) => $c->where('status', $status))
            ->when($q !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower((string) $r['member']?->name), $q)))
            ->values();

        $payments = $event ? DonationPayment::whereIn('pledge_id', $progress->pluck('pledge.id'))->orderByDesc('paid_on')->orderByDesc('id')->get()->groupBy('pledge_id') : collect();

        return view('donations.index', [
            'events' => $events,
            'event' => $event,
            'rows' => $rows,
            'totals' => $totals,
            'status' => $status,
            'payments' => $payments,
            'canManage' => Gate::allows('donations.manage'),
        ]);
    }

    public function storePledge(Request $request): RedirectResponse
    {
        Gate::authorize('donations.manage');
        $v = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at'),
                Rule::unique('donation_pledges', 'member_id')->where('event_id', $request->integer('event_id'))],
            'expected_amount' => ['nullable', 'integer', 'min:0', 'max:99999999999999'],
        ], ['member_id.unique' => 'ສະມາຊິກນີ້ມີໃນລາຍການບໍລິຈາກຂອງກິດຈະກຳນີ້ແລ້ວ', 'member_id.required' => 'ກະລຸນາເລືອກສະມາຊິກ']);
        $pledge = new DonationPledge($v);
        $pledge->created_by = $request->user()->id;
        $pledge->save();
        AuditLogger::log('pledge_create', $pledge, $pledge->member->name.' '.($pledge->expected_amount ? money($pledge->expected_amount) : '—'));

        return redirect()->route('donations.index', ['event' => $v['event_id']])->with('success', 'ເພີ່ມຜູ້ບໍລິຈາກແລ້ວ');
    }

    public function updatePledge(Request $request, DonationPledge $pledge): RedirectResponse
    {
        Gate::authorize('donations.manage');
        $v = $request->validate(['expected_amount' => ['nullable', 'integer', 'min:0', 'max:99999999999999']]);
        $before = $pledge->expected_amount;
        $pledge->update(['expected_amount' => $v['expected_amount'] ?? null]);
        AuditLogger::log('pledge_update', $pledge, $pledge->member->name.' '.money($before).' → '.money($pledge->expected_amount));

        return back()->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function destroyPledge(DonationPledge $pledge): RedirectResponse
    {
        Gate::authorize('donations.manage');
        if ($pledge->payments()->exists()) {
            return back()->with('error', 'ຜູ້ບໍລິຈາກນີ້ມີການຊຳລະແລ້ວ — ລຶບການຊຳລະກ່ອນ');
        }
        AuditLogger::log('pledge_delete', $pledge, $pledge->member?->name);
        $pledge->delete();

        return back()->with('success', 'ລຶບແລ້ວ');
    }

    /** record_donation_payment — pledge_id, or member_id (+ event) to create a pledge without an expected amount. */
    public function storePayment(Request $request): RedirectResponse
    {
        Gate::authorize('donations.manage');
        $v = $request->validate([
            'pledge_id' => ['nullable', 'integer', 'exists:donation_pledges,id', 'required_without:member_id'],
            'member_id' => ['nullable', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at'), 'required_without:pledge_id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id', 'required_with:member_id'],
            'amount' => Rules::amount(),
            'paid_on' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateString()],
            'method' => ['required', Rule::in(array_keys(Lookups::PAYMENT_METHODS))],
            'note' => ['nullable', 'string', 'max:255'],
            'slip' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
            'client_request_id' => ['nullable', 'string', 'max:64'],
        ], [...Rules::messages(), 'pledge_id.required_without' => 'ກະລຸນາເລືອກຜູ້ບໍລິຈາກ', 'member_id.required_without' => 'ກະລຸນາເລືອກຜູ້ບໍລິຈາກ']);

        $pledge = ! empty($v['pledge_id'])
            ? DonationPledge::findOrFail($v['pledge_id'])
            : DB::transaction(fn () => DonationPledge::firstOrCreate(['member_id' => $v['member_id'], 'event_id' => $v['event_id']], ['expected_amount' => null]));

        $slip = $request->hasFile('slip') ? $this->media->storeImage($request->file('slip'), 'slips')['path'] : null;
        $this->finance->recordPayment($pledge, (int) $v['amount'], $v['paid_on'], $v['method'], $v['note'] ?? null, $slip, $v['client_request_id'] ?? null, $request->user());

        return redirect()->route('donations.index', ['event' => $pledge->event_id])->with('success', 'ບັນທຶກການຊຳລະແລ້ວ — ລົງເປັນລາຍຮັບໃນການເງິນອັດຕະໂນມັດ');
    }

    public function updatePayment(Request $request, DonationPayment $payment): RedirectResponse
    {
        Gate::authorize('donations.manage');
        $v = $request->validate([
            'amount' => Rules::amount(),
            'paid_on' => ['required', 'date'],
            'method' => ['required', Rule::in(array_keys(Lookups::PAYMENT_METHODS))],
            'note' => ['nullable', 'string', 'max:255'],
        ], Rules::messages());
        $this->finance->updatePayment($payment, (int) $v['amount'], $v['paid_on'], $v['method'], $v['note'] ?? null, $request->user());

        return back()->with('success', 'ແກ້ໄຂການຊຳລະແລ້ວ (ທຸລະກຳໃນການເງິນອັບເດດນຳ)');
    }

    public function destroyPayment(Request $request, DonationPayment $payment): RedirectResponse
    {
        Gate::authorize('donations.manage');
        $slip = $payment->slip_path;
        $this->finance->deletePayment($payment, $request->user());
        $this->media->delete($slip);

        return back()->with('success', 'ລຶບການຊຳລະແລ້ວ (ທຸລະກຳໃນການເງິນຖືກລຶບນຳ)');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('donations.view');
        $event = Event::findOrFail($request->integer('event'));
        $rows = $this->finance->donationProgress($event)->map(fn ($r) => [
            $r['member']?->name, $r['expected'], $r['paid'], $r['outstanding'], $r['pct'] === null ? '' : Format::percent($r['pct']),
            FinanceService::statusLabel($r['status'])[0], $r['last_paid_on'] ? Format::date($r['last_paid_on']) : '',
        ]);

        return Csv::download('donations-'.$event->id, ['ຜູ້ບໍລິຈາກ', 'ຄາດຫວັງ (₭)', 'ຊຳລະແລ້ວ (₭)', 'ຄ້າງ (₭)', 'ຄວາມຄືບໜ້າ', 'ສະຖານະ', 'ຊຳລະລ່າສຸດ'], $rows);
    }
}
