<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_entries', function (Blueprint $table) {
            $table->dropUnique('pool_entries_tournament_id_user_id_unique');
        });
    }

    public function down(): void
    {
        $hasDuplicates = DB::table('pool_entries')
            ->select('tournament_id', 'user_id')
            ->groupBy('tournament_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            // Keep rollback resilient when historical data already allows multiple entries.
            return;
        }

        Schema::table('pool_entries', function (Blueprint $table) {
            $table->unique(['tournament_id', 'user_id']);
        });
    }
};
