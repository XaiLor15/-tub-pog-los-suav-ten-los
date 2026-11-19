<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts. The Handoff's Supabase `profiles` + `user_roles` are merged into one
 * `users` table (Laravel auth): display_name, avatar, theme, font_scale, member link,
 * role (pending|member|admin), approved_by/at and is_disabled.
 * "Guest" is not stored — it is simply an unauthenticated visitor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('display_name', 100);
            $table->string('email', 150)->unique();
            $table->string('password')->nullable(); // null for Google-only accounts
            $table->string('google_id', 64)->nullable()->unique();
            $table->enum('auth_provider', ['email', 'google'])->default('email');
            $table->string('avatar_path')->nullable();
            // New accounts are ALWAYS pending until an Admin approves them.
            $table->enum('role', ['pending', 'member', 'admin'])->default('pending');
            $table->boolean('is_disabled')->default(false);
            $table->unsignedBigInteger('member_id')->nullable()->unique(); // FK added with members table
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->enum('theme', ['light', 'dark', 'system'])->default('system');
            $table->unsignedSmallInteger('font_scale')->default(100); // 100 | 112 | 125
            $table->unsignedInteger('failed_login_count')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index(['role', 'is_disabled']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_font_scale CHECK (font_scale IN (100, 112, 125))');
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
