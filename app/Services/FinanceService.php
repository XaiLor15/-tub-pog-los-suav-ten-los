<?php

namespace App\Services;

use App\Models\DonationPayment;
use App\Models\DonationPledge;
use App\Models\Event;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Lookups;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Transactions + donation payments.
 *
 * A donation payment and its income transaction are ALWAYS created, edited and deleted
 * together (record_donation_payment / update_ / delete_donation_payment RPCs). Totals are
 * read only from `transactions`, so a donation is counted exactly once.
 * Donation-sourced transactions cannot be changed from the Finance page
 * (block_donation_tx_direct_edit).
 */
class FinanceService
{
    /**
     * @param  array{date:string, description:string, category:string, type:string, amount:int, event_id:?int}  $data
     */
    public function createTransaction(array $data, ?string $clientRequestId, User $actor): Transaction
    {
        if ($clientRequestId && ($existing = Transaction::where('client_request_id', $clientRequestId)->first())) {
            return $existing; // idempotent re-submit (double click / retry)
        }

        try {
            return DB::transaction(function () use ($data, $clientRequestId, $actor) {
                $tx = new Transaction($data);
                $tx->source = 'manual';
                $tx->client_request_id = $clientRequestId;
                $tx->created_by = $actor->id;
                $tx->updated_by = $actor->id;
                $tx->save();
                AuditLogger::log('tx_create', $tx, $this->describe($tx), [], $actor);

                return $tx;
            });
        } catch (UniqueConstraintViolationException) {
            return Transaction::where('client_request_id', $clientRequestId)->firstOrFail();
        }
    }

    /** @param  array<string, mixed>  $data */
    public function updateTransaction(Transaction $tx, array $data, User $actor): Transaction
    {
        $this->assertManual($tx);

        return DB::transaction(function () use ($tx, $data, $actor) {
            $before = $this->describe($tx);
            $tx->fill($data);
            $tx->updated_by = $actor->id;
            $tx->save();
            AuditLogger::log('tx_update', $tx, $this->describe($tx), ['before' => $before], $actor);

            return $tx;
        });
    }

    public function deleteTransaction(Transaction $tx, User $actor): void
    {
        $this->assertManual($tx);
        DB::transaction(function () use ($tx, $actor) {
            AuditLogger::log('tx_delete', $tx, $this->describe($tx), $tx->only(['date', 'type', 'amount', 'category']), $actor);
            $tx->delete();
        });
    }

    /** find_similar_transactions(date, amount, category): same amount + category within ±24h. */
    public function findSimilar(string $date, int $amount, string $category, ?int $excludeId = null): Collection
    {
        $d = Carbon::parse($date);

        return Transaction::whereBetween('date', [$d->copy()->subDay()->toDateString(), $d->copy()->addDay()->toDateString()])
            ->where('amount', $amount)->where('category', $category)
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->limit(5)->get();
    }

    /**
     * record_donation_payment(pledge_id, amount, paid_on, method, note, client_request_id)
     * Creates the payment AND its single income transaction in one DB transaction.
     */
    public function recordPayment(DonationPledge $pledge, int $amount, string $paidOn, string $method, ?string $note, ?string $slipPath, ?string $clientRequestId, User $actor): DonationPayment
    {
        if ($clientRequestId && ($tx = Transaction::where('client_request_id', $clientRequestId)->first())) {
            return $tx->donationPayment()->firstOrFail();
        }
        $this->assertAmount($amount);

        try {
            return DB::transaction(function () use ($pledge, $amount, $paidOn, $method, $note, $slipPath, $clientRequestId, $actor) {
                $pledge = DonationPledge::with(['member', 'event'])->lockForUpdate()->findOrFail($pledge->id);
                $tx = new Transaction([
                    'date' => $paidOn,
                    'description' => $this->donationDescription($pledge),
                    'category' => Lookups::DONATION_CATEGORY,
                    'type' => 'income',
                    'amount' => $amount,
                    'event_id' => $pledge->event_id,
                ]);
                $tx->source = 'donation';
                $tx->client_request_id = $clientRequestId;
                $tx->created_by = $actor->id;
                $tx->updated_by = $actor->id;
                $tx->save();

                $payment = DonationPayment::create([
                    'pledge_id' => $pledge->id, 'transaction_id' => $tx->id, 'amount' => $amount,
                    'paid_on' => $paidOn, 'method' => $method, 'note' => $note, 'slip_path' => $slipPath,
                    'created_by' => $actor->id,
                ]);
                AuditLogger::log('payment_create', $payment, $pledge->member->name.' '.money($amount), ['event_id' => $pledge->event_id], $actor);

                return $payment;
            });
        } catch (UniqueConstraintViolationException) {
            return Transaction::where('client_request_id', $clientRequestId)->firstOrFail()->donationPayment()->firstOrFail();
        }
    }

    public function updatePayment(DonationPayment $payment, int $amount, string $paidOn, string $method, ?string $note, User $actor): DonationPayment
    {
        $this->assertAmount($amount);

        return DB::transaction(function () use ($payment, $amount, $paidOn, $method, $note, $actor) {
            $payment = DonationPayment::with('pledge.member')->lockForUpdate()->findOrFail($payment->id);
            $before = $payment->amount;
            $payment->update(['amount' => $amount, 'paid_on' => $paidOn, 'method' => $method, 'note' => $note]);
            Transaction::whereKey($payment->transaction_id)->update(['amount' => $amount, 'date' => $paidOn, 'updated_by' => $actor->id]);
            AuditLogger::log('payment_update', $payment, $payment->pledge->member->name.' '.money($before).' → '.money($amount), [], $actor);

            return $payment;
        });
    }

    public function deletePayment(DonationPayment $payment, User $actor): void
    {
        DB::transaction(function () use ($payment, $actor) {
            $payment = DonationPayment::with('pledge.member')->lockForUpdate()->findOrFail($payment->id);
            AuditLogger::log('payment_delete', $payment, $payment->pledge->member->name.' '.money($payment->amount), [], $actor);
            $txId = $payment->transaction_id;
            $payment->delete();
            Transaction::whereKey($txId)->delete();
        });
    }

    /**
     * v_finance_summary(from, to)
     *
     * @return array{total_income:int, total_expense:int, balance:int, donation_income:int, count:int}
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        $row = Transaction::query()->between($from, $to)->selectRaw(
            "COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) as income,
             COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) as expense,
             COALESCE(SUM(CASE WHEN source='donation' THEN amount ELSE 0 END),0) as donation,
             COUNT(*) as cnt"
        )->first();

        return [
            'total_income' => (int) $row->income,
            'total_expense' => (int) $row->expense,
            'balance' => (int) $row->income - (int) $row->expense,
            'donation_income' => (int) $row->donation,
            'count' => (int) $row->cnt,
        ];
    }

    /**
     * v_finance_monthly: last N months (oldest first), zero-filled.
     *
     * @return array<int, array{month:string, label:string, income:int, expense:int}>
     */
    public function monthly(int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $rows = Transaction::whereDate('date', '>=', $start)->get(['date', 'type', 'amount'])
            ->groupBy(fn ($t) => $t->date->format('Y-m'));
        $labels = [1 => 'ມ.ກ', 'ກ.ພ', 'ມ.ນ', 'ມ.ສ', 'ພ.ພ', 'ມິ.ຖ', 'ກ.ລ', 'ສ.ຫ', 'ກ.ຍ', 'ຕ.ລ', 'ພ.ຈ', 'ທ.ວ'];
        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i);
            $g = $rows->get($m->format('Y-m'), collect());
            $out[] = [
                'month' => $m->format('Y-m'),
                'label' => $labels[$m->month],
                'income' => (int) $g->where('type', 'income')->sum('amount'),
                'expense' => (int) $g->where('type', 'expense')->sum('amount'),
            ];
        }

        return $out;
    }

    /**
     * v_donation_progress for one event: one row per pledge.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function donationProgress(Event $event): Collection
    {
        $pledges = DonationPledge::with('member')->where('event_id', $event->id)
            ->withSum('payments as paid_total', 'amount')
            ->withMax('payments as last_paid_on', 'paid_on')
            ->get();

        return $pledges->map(function (DonationPledge $p) {
            $expected = $p->expected_amount;
            $paid = (int) $p->paid_total;

            return [
                'pledge' => $p,
                'member' => $p->member,
                'expected' => $expected,
                'paid' => $paid,
                'outstanding' => $expected ? max(0, $expected - $paid) : 0,
                'pct' => $expected ? round($paid / $expected * 100, 1) : null,
                'status' => self::donationStatus($expected, $paid),
                'last_paid_on' => $p->last_paid_on,
            ];
        })->sortBy(fn ($r) => $r['member']?->name)->values();
    }

    /** Status rules: expected=0/null → paid if any paid, else unpaid; paid=0 → unpaid; < → partial; = → paid; > → over. */
    public static function donationStatus(?int $expected, int $paid): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }
        if (! $expected) {
            return 'paid';
        }

        return match (true) {
            $paid < $expected => 'partial',
            $paid > $expected => 'over',
            default => 'paid',
        };
    }

    /**
     * v_donation_event_totals
     *
     * @return array{expected:int, paid:int, outstanding:int, pct:?float, count:int, paid_count:int, partial:int, unpaid:int, over:int, outstanding_people:int}
     */
    public function eventTotals(Collection $progress): array
    {
        $expected = (int) $progress->sum('expected');
        $paid = (int) $progress->sum('paid');
        $counts = $progress->countBy('status');

        return [
            'expected' => $expected,
            'paid' => $paid,
            'outstanding' => (int) $progress->sum('outstanding'),
            'pct' => $expected ? round($paid / $expected * 100, 1) : null,
            'count' => $progress->count(),
            'paid_count' => (int) ($counts['paid'] ?? 0),
            'partial' => (int) ($counts['partial'] ?? 0),
            'unpaid' => (int) ($counts['unpaid'] ?? 0),
            'over' => (int) ($counts['over'] ?? 0),
            'outstanding_people' => $progress->where('outstanding', '>', 0)->count(),
        ];
    }

    public static function statusLabel(string $status): array
    {
        return match ($status) {
            'paid' => ['ຄົບແລ້ວ', 'success'],
            'partial' => ['ບາງສ່ວນ', 'warning'],
            'over' => ['ເກີນ', 'info'],
            default => ['ຍັງບໍ່ຈ່າຍ', 'destructive'],
        };
    }

    private function assertManual(Transaction $tx): void
    {
        if ($tx->isFromDonation()) {
            throw ValidationException::withMessages(['transaction' => 'ທຸລະກຳນີ້ມາຈາກການບໍລິຈາກ — ແກ້ໄຂ ຫຼື ລຶບໄດ້ຈາກໜ້າຕິດຕາມການບໍລິຈາກເທົ່ານັ້ນ']);
        }
    }

    private function assertAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'ຈຳນວນເງິນຕ້ອງຫຼາຍກວ່າ 0']);
        }
    }

    private function donationDescription(DonationPledge $pledge): string
    {
        return 'ບໍລິຈາກ '.($pledge->event?->title ?? '').' — '.($pledge->member?->name ?? '');
    }

    private function describe(Transaction $tx): string
    {
        return ($tx->type === 'expense' ? 'ລາຍຈ່າຍ ' : 'ລາຍຮັບ ').money($tx->amount).' · '.$tx->description;
    }
}
