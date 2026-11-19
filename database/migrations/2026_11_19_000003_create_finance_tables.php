<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Finance. Money is DECIMAL(14,0) (Lao Kip, no decimals).
 *
 * Double-counting guard: every donation payment owns exactly ONE income transaction
 * (donation_payments.transaction_id UNIQUE), created/updated/deleted together inside a
 * DB transaction by DonationService. Finance totals read only `transactions`, so a
 * donation is counted once. Transactions with source='donation' cannot be edited from
 * the Finance page (enforced in TransactionPolicy + service).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description', 255);
            $table->string('category', 50);
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 14, 0);
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->enum('source', ['manual', 'donation'])->default('manual');
            $table->string('client_request_id', 64)->nullable()->unique(); // idempotency key
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('date');
            $table->index('event_id');
            $table->index(['type', 'date']);
        });

        Schema::create('donation_pledges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->decimal('expected_amount', 14, 0)->nullable(); // null = no expected amount
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['member_id', 'event_id']);
        });

        Schema::create('donation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pledge_id')->constrained('donation_pledges')->restrictOnDelete();
            $table->foreignId('transaction_id')->unique()->constrained('transactions')->restrictOnDelete();
            $table->decimal('amount', 14, 0);
            $table->date('paid_on');
            $table->enum('method', ['cash', 'bank']);
            $table->string('note', 255)->nullable();
            $table->string('slip_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['pledge_id', 'paid_on']);
        });

        Schema::create('fund_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained('members')->restrictOnDelete();
            $table->date('joined_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_tx_amount CHECK (amount > 0)');
            DB::statement('ALTER TABLE donation_payments ADD CONSTRAINT chk_payment_amount CHECK (amount > 0)');
            DB::statement('ALTER TABLE donation_pledges ADD CONSTRAINT chk_pledge_amount CHECK (expected_amount IS NULL OR expected_amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_members');
        Schema::dropIfExists('donation_payments');
        Schema::dropIfExists('donation_pledges');
        Schema::dropIfExists('transactions');
    }
};
