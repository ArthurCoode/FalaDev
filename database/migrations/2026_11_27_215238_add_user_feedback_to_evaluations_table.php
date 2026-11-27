<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Este feedback foi útil?" at the end of the report (section 12).
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->boolean('user_found_useful')->nullable()->after('next_steps');
            $table->string('user_comment', 500)->nullable()->after('user_found_useful');
            $table->timestamp('user_rated_at')->nullable()->after('user_comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['user_found_useful', 'user_comment', 'user_rated_at']);
        });
    }
};
