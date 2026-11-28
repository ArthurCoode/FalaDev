<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // One row per user: the streak and the XP total.
        Schema::create('study_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            // Study days are dates in config('faladev.study.timezone').
            $table->date('last_study_on')->nullable();
            $table->unsignedTinyInteger('freezes')->default(0);
            $table->unsignedInteger('total_xp')->default(0);
            $table->timestamps();
        });

        // Each finished lesson or review. attempt_id makes finishing idempotent:
        // sending the same attempt twice returns the first result.
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('attempt_id');
            $table->string('kind', 16);
            $table->string('lesson_slug', 80)->nullable();
            $table->unsignedSmallInteger('correct');
            $table->unsignedSmallInteger('total');
            $table->unsignedSmallInteger('xp');
            $table->date('study_on');
            $table->json('outcome');
            $table->timestamps();

            $table->unique(['user_id', 'attempt_id']);
            $table->index(['user_id', 'kind', 'lesson_slug']);
        });

        // The review queue: phrases missed in a lesson come back after
        // 1, 3, 7, 14 and 30 days (App\Study\ReviewSchedule).
        Schema::create('study_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('exercise_key', 120);
            $table->unsignedTinyInteger('box')->default(0);
            $table->date('due_on')->nullable();
            $table->unsignedSmallInteger('lapses')->default(0);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('mastered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'exercise_key']);
            $table->index(['user_id', 'mastered_at', 'due_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_cards');
        Schema::dropIfExists('study_sessions');
        Schema::dropIfExists('study_progress');
    }
};
