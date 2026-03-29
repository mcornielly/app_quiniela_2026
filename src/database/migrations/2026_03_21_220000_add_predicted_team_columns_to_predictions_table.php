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
        Schema::table('predictions', function (Blueprint $table) {
            $table->foreignId('predicted_home_team_id')
                ->nullable()
                ->after('game_id')
                ->constrained('teams')
                ->nullOnDelete();

            $table->foreignId('predicted_away_team_id')
                ->nullable()
                ->after('predicted_home_team_id')
                ->constrained('teams')
                ->nullOnDelete();

            $table->foreignId('predicted_winner_team_id')
                ->nullable()
                ->after('predicted_away_team_id')
                ->constrained('teams')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('predicted_winner_team_id');
            $table->dropConstrainedForeignId('predicted_away_team_id');
            $table->dropConstrainedForeignId('predicted_home_team_id');
        });
    }
};

