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
        // The latest passing code per user and challenge. The tests run in the
        // browser, so "solved" is what the page reports.
        Schema::create('challenge_solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('challenge_slug', 80);
            $table->text('code');
            $table->timestamp('first_solved_at');
            $table->timestamp('last_solved_at');
            $table->timestamps();

            $table->unique(['user_id', 'challenge_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenge_solutions');
    }
};
