<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Family data: members (Phaj = `generation`), spouse relations, leadership, elders.
 * Rollback: drops these tables (fresh install only — never run down() on production data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('hmong_name', 100)->nullable();
            $table->enum('sex', ['male', 'female']);
            $table->date('birth_date')->nullable();
            $table->boolean('birth_year_only')->default(false); // "ຮູ້ແຕ່ປີ" → stored as 01/01/YYYY
            $table->unsignedSmallInteger('death_year')->nullable();
            $table->unsignedSmallInteger('generation'); // UI label: Phaj
            $table->boolean('is_alive')->default(true);
            $table->foreignId('father_id')->nullable()->constrained('members')->restrictOnDelete();
            $table->foreignId('mother_id')->nullable()->constrained('members')->restrictOnDelete(); // read-only in UI
            $table->unsignedSmallInteger('birth_order')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('province', 60)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('generation');
            $table->index('father_id');
            $table->index('mother_id');
            $table->index(['province', 'village']);
            // Soft-deleted / unlinked members get birth_order = NULL, so a plain UNIQUE is enough
            // (NULLs never collide) and works on both MySQL and SQLite.
            $table->unique(['father_id', 'birth_order'], 'ux_members_father_birth_order');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->nullOnDelete();
        });

        Schema::create('member_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('related_member_id')->constrained('members')->cascadeOnDelete();
            $table->enum('relation_type', ['spouse'])->default('spouse');
            $table->timestamps();
            $table->unique(['member_id', 'related_member_id', 'relation_type'], 'ux_member_relations_pair');
        });

        Schema::create('leadership', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->string('position', 100);
            $table->enum('group', ['executive', 'department_head', 'committee']);
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->index('sort_order');
            $table->index(['group', 'sort_order']);
        });

        Schema::create('elders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained('members')->restrictOnDelete();
            $table->string('role_title', 100);
            $table->enum('role_category', ['rite', 'qeej', 'herb', 'history', 'craft', 'mediate']);
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->enum('status', ['active', 'retired'])->default('active');
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->index('role_category');
        });

        Schema::create('elder_successors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elder_id')->constrained('elders')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['elder_id', 'member_id']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE members ADD CONSTRAINT chk_members_birth_order CHECK (birth_order IS NULL OR birth_order > 0)');
            DB::statement('ALTER TABLE members ADD CONSTRAINT chk_members_generation CHECK (generation >= 1)');
            // "father_id <> id" cannot be a CHECK on MySQL/MariaDB (AUTO_INCREMENT column);
            // self-parenting and longer cycles are rejected in FamilyService::assertValidFather().
            DB::statement('ALTER TABLE member_relations ADD CONSTRAINT chk_relations_not_self CHECK (member_id <> related_member_id)');
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['member_id']));
        // The linked member rows are dropped below, so the links would dangle on re-migrate.
        DB::table('users')->update(['member_id' => null]);
        // members references itself (father_id / mother_id); drop without per-row FK checks.
        Schema::withoutForeignKeyConstraints(function () {
            Schema::dropIfExists('elder_successors');
            Schema::dropIfExists('elders');
            Schema::dropIfExists('leadership');
            Schema::dropIfExists('member_relations');
            Schema::dropIfExists('members');
        });
    }
};
