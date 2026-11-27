<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Survives the session's deletion, so deleting an interview never
            // gives the allowance back (FalaDev.md, sections 9 and 10).
            $table->foreignId('interview_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 32);
            $table->timestamps();

            // "How many interviews did this user start this month?"
            $table->index(['user_id', 'reason', 'created_at']);
        });

        // Interviews started before this table existed still count.
        DB::table('usage_records')->insertUsing(
            ['user_id', 'interview_session_id', 'reason', 'created_at', 'updated_at'],
            DB::table('interview_sessions')
                ->whereNotNull('started_at')
                ->select(['user_id', 'id', DB::raw("'interview_started'"), 'started_at', 'started_at']),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
