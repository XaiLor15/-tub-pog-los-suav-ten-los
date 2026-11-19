<?php

namespace Tests\Feature;

use App\Models\DonationPayment;
use App\Models\DonationPledge;
use App\Models\Transaction;
use App\Services\FinanceService;

/** Money rules: donations counted once, idempotent submits, donation transactions locked. */
class FinanceTest extends AppTestCase
{
    private function pledge(?int $expected = 1000000): DonationPledge
    {
        return DonationPledge::create(['member_id' => $this->member('ເປັງ')->id, 'event_id' => $this->event()->id, 'expected_amount' => $expected]);
    }

    public function test_a_donation_payment_is_counted_exactly_once_in_income(): void
    {
        $admin = $this->admin();
        $pledge = $this->pledge();
        app(FinanceService::class)->createTransaction(['date' => now()->toDateString(), 'description' => 'ຄ່າບຳລຸງ', 'category' => 'ຄ່າບຳລຸງ', 'type' => 'income', 'amount' => 500000, 'event_id' => null], null, $admin);

        $this->actingAs($admin)->post('/donation-tracking/payments', [
            'pledge_id' => $pledge->id, 'amount' => 300000, 'paid_on' => now()->toDateString(), 'method' => 'cash', 'client_request_id' => 'req-1',
        ])->assertRedirect();

        $this->assertSame(1, DonationPayment::count());
        $this->assertSame(1, Transaction::where('source', 'donation')->count());
        $summary = app(FinanceService::class)->summary();
        $this->assertSame(800000, $summary['total_income']);       // 500k manual + 300k donation — not 1.1M
        $this->assertSame(300000, $summary['donation_income']);
        $this->assertSame(300000, (int) app(FinanceService::class)->donationProgress($pledge->event)->first()['paid']);
    }

    public function test_resubmitting_the_same_payment_does_not_duplicate(): void
    {
        $admin = $this->admin();
        $pledge = $this->pledge();
        $payload = ['pledge_id' => $pledge->id, 'amount' => 300000, 'paid_on' => now()->toDateString(), 'method' => 'cash', 'client_request_id' => 'same-uuid'];

        $this->actingAs($admin)->post('/donation-tracking/payments', $payload);
        $this->actingAs($admin)->post('/donation-tracking/payments', $payload);

        $this->assertSame(1, DonationPayment::count());
        $this->assertSame(1, Transaction::count());
    }

    public function test_resubmitting_the_same_transaction_does_not_duplicate(): void
    {
        $admin = $this->admin();
        $payload = ['date' => now()->toDateString(), 'description' => 'ອາຫານ', 'type' => 'expense', 'category' => 'ອາຫານ', 'amount' => 100000, 'client_request_id' => 'tx-uuid'];
        $this->actingAs($admin)->post('/finance/transactions', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/finance/transactions', $payload)->assertRedirect();
        $this->assertSame(1, Transaction::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'tx_create']);
    }

    public function test_updating_and_deleting_a_payment_keeps_its_transaction_in_sync(): void
    {
        $admin = $this->admin();
        $svc = app(FinanceService::class);
        $payment = $svc->recordPayment($this->pledge(), 200000, now()->toDateString(), 'cash', null, null, null, $admin);

        $this->actingAs($admin)->put('/donation-tracking/payments/'.$payment->id, ['amount' => 250000, 'paid_on' => now()->toDateString(), 'method' => 'bank'])->assertRedirect();
        $this->assertSame(250000, $payment->transaction->fresh()->amount);
        $this->assertSame(250000, $svc->summary()['total_income']);

        $this->actingAs($admin)->delete('/donation-tracking/payments/'.$payment->id)->assertRedirect();
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, $svc->summary()['total_income']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_delete']);
    }

    public function test_donation_transactions_cannot_be_edited_from_finance(): void
    {
        $admin = $this->admin();
        $payment = app(FinanceService::class)->recordPayment($this->pledge(), 200000, now()->toDateString(), 'cash', null, null, null, $admin);
        $tx = $payment->transaction;

        $this->actingAs($admin)->put('/finance/transactions/'.$tx->id, ['date' => now()->toDateString(), 'description' => 'x', 'type' => 'income', 'category' => 'ຄ່າບຳລຸງ', 'amount' => 999])->assertSessionHasErrors('transaction');
        $this->actingAs($admin)->delete('/finance/transactions/'.$tx->id)->assertSessionHasErrors('transaction');
        $this->assertSame(200000, $tx->fresh()->amount);
    }

    public function test_manual_income_cannot_use_donation_category(): void
    {
        $this->actingAs($this->admin())->post('/finance/transactions', ['date' => now()->toDateString(), 'description' => 'x', 'type' => 'income', 'category' => 'ບໍລິຈາກ', 'amount' => 100])
            ->assertSessionHasErrors('category');
        $this->assertSame(0, Transaction::count());
    }

    public function test_amount_must_be_positive_whole_kip(): void
    {
        $admin = $this->admin();
        foreach ([0, -5, 12.5] as $bad) {
            $this->actingAs($admin)->post('/finance/transactions', ['date' => now()->toDateString(), 'description' => 'x', 'type' => 'expense', 'category' => 'ອາຫານ', 'amount' => $bad])->assertSessionHasErrors('amount');
        }
        $this->assertSame(0, Transaction::count());
    }

    public function test_similar_transactions_within_24_hours_are_reported(): void
    {
        $admin = $this->admin();
        app(FinanceService::class)->createTransaction(['date' => now()->toDateString(), 'description' => 'ອາຫານ', 'category' => 'ອາຫານ', 'type' => 'expense', 'amount' => 5000, 'event_id' => null], null, $admin);
        $this->actingAs($admin)->getJson('/finance/similar?date='.now()->addDay()->toDateString().'&amount=5000&category=ອາຫານ')->assertOk()->assertJsonCount(1);
        $this->actingAs($admin)->getJson('/finance/similar?date='.now()->addDays(3)->toDateString().'&amount=5000&category=ອາຫານ')->assertOk()->assertJsonCount(0);
    }

    public function test_donation_status_rules(): void
    {
        $this->assertSame('unpaid', FinanceService::donationStatus(1000, 0));
        $this->assertSame('partial', FinanceService::donationStatus(1000, 500));
        $this->assertSame('paid', FinanceService::donationStatus(1000, 1000));
        $this->assertSame('over', FinanceService::donationStatus(1000, 1500));
        $this->assertSame('paid', FinanceService::donationStatus(null, 200));
        $this->assertSame('unpaid', FinanceService::donationStatus(null, 0));
    }

    public function test_payment_for_new_donor_creates_pledge_without_expectation(): void
    {
        $admin = $this->admin();
        $event = $this->event();
        $m = $this->member('ກ້ຽງ');
        $this->actingAs($admin)->post('/donation-tracking/payments', ['member_id' => $m->id, 'event_id' => $event->id, 'amount' => 200000, 'paid_on' => now()->toDateString(), 'method' => 'cash'])->assertRedirect();
        $this->assertDatabaseHas('donation_pledges', ['member_id' => $m->id, 'event_id' => $event->id, 'expected_amount' => null]);
        $this->assertSame(1, Transaction::count());
    }

    public function test_members_can_view_donation_and_fund_pages_but_not_write(): void
    {
        $member = $this->user('member');
        $pledge = $this->pledge();
        $this->actingAs($member)->get('/donation-tracking?event='.$pledge->event_id)->assertOk()->assertSee('ເປັງ')->assertDontSee('data-modal-open="pay-form"', false);
        $this->actingAs($member)->get('/fund-contributors')->assertOk();
        $this->actingAs($member)->post('/donation-tracking/payments', ['pledge_id' => $pledge->id, 'amount' => 1, 'paid_on' => now()->toDateString(), 'method' => 'cash'])->assertForbidden();
        $this->actingAs($member)->post('/fund-contributors', ['member_id' => $pledge->member_id, 'joined_at' => now()->toDateString()])->assertForbidden();
        $this->assertSame(0, Transaction::count());
    }

    public function test_csv_exports_have_bom_and_neutralise_formulas(): void
    {
        $admin = $this->admin();
        app(FinanceService::class)->createTransaction(['date' => now()->toDateString(), 'description' => '=HYPERLINK("x")', 'category' => 'ອາຫານ', 'type' => 'expense', 'amount' => 5000, 'event_id' => null], null, $admin);
        $csv = $this->actingAs($admin)->get('/finance/export.csv')->assertOk()->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }
}
