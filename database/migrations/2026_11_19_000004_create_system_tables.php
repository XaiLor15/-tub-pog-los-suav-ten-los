<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('entity', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('summary', 500)->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity', 'entity_id']);
            $table->index('created_at');
            $table->index('action');
        });

        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('item_key', 64); // event:<id> | news:<id> | pending_users
            $table->timestamp('read_at')->useCurrent();
            $table->unique(['user_id', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('audit_logs');
    }
};
