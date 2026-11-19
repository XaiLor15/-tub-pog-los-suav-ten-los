<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Activities (events + participants), news + comments, and shared media items. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location', 200)->nullable();
            $table->string('organizer', 150)->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('cover_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('start_date');
            $table->index(['is_public', 'start_date']);
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->string('duty', 100)->nullable();
            $table->enum('status', ['confirmed', 'maybe'])->default('confirmed');
            $table->timestamps();
            $table->unique(['event_id', 'member_id']);
            $table->index('event_id');
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('body');
            $table->string('category', 50);
            $table->json('tags')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'is_pinned', 'published_at']);
        });

        Schema::create('news_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['news_id', 'created_at']);
        });

        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->enum('module', ['event', 'news']);
            $table->unsignedBigInteger('record_id');
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['module', 'record_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
        Schema::dropIfExists('news_comments');
        Schema::dropIfExists('news');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('events');
    }
};
