<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Technical events for the pilot metrics (FalaDev.md, section 12):
     * microphone checks and failures, transcription and report failures.
     * Never audio, transcripts or answers.
     */
    public function up(): void
    {
        Schema::create('pilot_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interview_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 48);
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_events');
    }
};
