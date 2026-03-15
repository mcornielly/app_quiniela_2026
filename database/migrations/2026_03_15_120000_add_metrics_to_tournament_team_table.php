<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_team', function (Blueprint $table) {
            $table->unsignedSmallInteger('fifa_ranking')->nullable()->after('team_id');
            $table->integer('fair_play_points')->default(0)->after('fifa_ranking');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_team', function (Blueprint $table) {
            $table->dropColumn(['fifa_ranking', 'fair_play_points']);
        });
    }
};
