<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Transaction;
use App\Services\FinanceService;
use App\Support\Csv;
use App\Support\Format;
use App\Support\Lookups;
use App\Support\Rules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Finance (/finance): Member = read-only, Admin = record / edit / delete. */
class FinanceController extends Controller
{
    public function __construct(private FinanceService $finance) {}

    public function index(Request $request): View
    {
        Gate::authorize('finance.view');
        [$from, $to] = $this->range($request);
        $query = $this->filtered($request, $from, $to);

        $rangeSummary = (clone $query)->selectRaw(
            "COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) as income,
             COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) as expense,
             COALESCE(SUM(CASE WHEN source='donation' THEN amount ELSE 0 END),0) as donation,
             SUM(CASE WHEN type='expense' THEN 1 ELSE 0 END) as expense_count"
        )->toBase()->first();

        return view('finance.index', [
            'transactions' => $query->with('event')->orderByDesc('date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'from' => $from,
            'to' => $to,
            'overall' => $this->finance->summary(),
            'range' => [
                'income' => (int) $rangeSummary->income,
                'expense' => (int) $rangeSummary->expense,
                'donation' => (int) $rangeSummary->donation,
                'expense_count' => (int) $rangeSummary->expense_count,
                'net' => (int) $rangeSummary->income - (int) $rangeSummary->expense,
            ],
            'events' => Event::orderByDesc('start_date')->get(['id', 'title']),
            'canManage' => Gate::allows('finance.manage'),
            'openForm' => Gate::allows('finance.manage') && ($request->boolean('new') || (session('errors')?->any() && old('_form') === 'tx')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('finance.manage');
        $data = $this->validated($request);
        $this->finance->createTransaction($data, $request->input('client_request_id'), $request->user());

        return redirect()->route('finance.index', $request->only(['type', 'from', 'to']))->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('finance.manage');
        $this->finance->updateTransaction($transaction, $this->validated($request), $request->user());

        return back()->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('finance.manage');
        $this->finance->deleteTransaction($transaction, $request->user());

        return back()->with('success', 'ລຶບທຸລະກຳແລ້ວ');
    }

    public function similar(Request $request): JsonResponse
    {
        Gate::authorize('finance.view');
        $v = $request->validate(['date' => ['required', 'date'], 'amount' => ['required', 'integer', 'min:1'], 'category' => ['required', 'string'], 'exclude' => ['nullable', 'integer']]);

        return response()->json($this->finance->findSimilar($v['date'], (int) $v['amount'], $v['category'], $v['exclude'] ?? null)->map(fn ($t) => [
            'date' => Format::date($t->date), 'description' => $t->description, 'amount' => Format::money($t->amount),
        ]));
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('finance.view');
        [$from, $to] = $this->range($request);
        $rows = $this->filtered($request, $from, $to)->with('event')->orderBy('date')->orderBy('id')->lazy(500)->map(fn (Transaction $t) => [
            Format::date($t->date), $t->description, $t->category, $t->type === 'income' ? 'ລາຍຮັບ' : 'ລາຍຈ່າຍ',
            $t->signedAmount(), $t->event?->title, $t->isFromDonation() ? 'ການບໍລິຈາກ' : 'ບັນທຶກເອງ',
        ]);

        return Csv::download('finance', ['ວັນທີ', 'ລາຍລະອຽດ', 'ໝວດ', 'ປະເພດ', 'ຈຳນວນ (₭)', 'ກິດຈະກຳ', 'ແຫຼ່ງ'], $rows);
    }

    /** @return array{0: string, 1: string} */
    private function range(Request $request): array
    {
        $from = $request->date('from')?->toDateString() ?? now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        return $from <= $to ? [$from, $to] : [$to, $from];
    }

    private function filtered(Request $request, string $from, string $to): Builder
    {
        return Transaction::query()->between($from, $to)
            ->when(in_array($request->query('type'), ['income', 'expense'], true), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->when($request->filled('event'), fn ($q) => $q->where('event_id', $request->integer('event')));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $type = $request->input('type');
        $cats = $type === 'expense' ? Lookups::EXPENSE_CATEGORIES : array_diff(Lookups::INCOME_CATEGORIES, [Lookups::DONATION_CATEGORY]);

        $v = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateString()],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', Rule::in($cats)],
            'amount' => Rules::amount(),
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'client_request_id' => ['nullable', 'string', 'max:64'],
        ], [
            ...Rules::messages(),
            'description.required' => 'ກະລຸນາໃສ່ລາຍລະອຽດ',
            'category.in' => 'ໝວດບໍ່ຖືກຕ້ອງ (ລາຍຮັບບໍລິຈາກ ບັນທຶກຜ່ານໜ້າຕິດຕາມການບໍລິຈາກ)',
        ]);
        unset($v['client_request_id']);

        return $v;
    }
}
