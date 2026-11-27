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
        Schema::create('interview_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('question');
            $table->boolean('is_follow_up')->default(false);
            // Null until the user confirms the (possibly edited) transcript.
            $table->text('answer_transcript')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->boolean('hint_used')->default(false);
            $table->unsignedSmallInteger('repeat_count')->default(0);
            // Operational data only (e.g. STT/LLM latency). Never secrets or raw audio.
            $table->json('metadata')->nullable();
            $table->timestamps();

            // One turn per position: guards against duplicated answers on retries.
            $table->unique(['interview_session_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_turns');
    }
};
