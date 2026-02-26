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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_game_id')->constrained('match_games')->cascadeOnDelete();

            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();

            // Para KO si empatan: quién pasa
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();

            $table->unsignedTinyInteger('points_awarded')->default(0);

            $table->timestamps();

            $table->unique(['pool_entry_id', 'match_game_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
