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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            // One report per interview; deleted with it (section 9).
            $table->foreignId('interview_session_id')->unique()->constrained()->cascadeOnDelete();
            // Which evaluator produced it ("fake" until the LLM exists).
            $table->string('evaluator', 32);
            // 1–5, a synthesis of the dimensions, never a hiring probability.
            $table->unsignedTinyInteger('overall_score');
            $table->text('summary');
            // Each item carries evidence from the transcript (section 7).
            $table->json('dimensions');
            $table->json('strengths');
            $table->json('improvements');
            $table->json('english_feedback');
            $table->json('next_steps');
            // Operational data only (e.g. latency, model). Never secrets.
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
