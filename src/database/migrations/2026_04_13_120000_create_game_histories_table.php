<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('game_id')
                ->nullable()
                ->constrained('games')
                ->nullOnDelete();

            $table->foreignId('home_team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            $table->foreignId('away_team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            $table->unsignedBigInteger('api_fixture_id')->nullable();
            $table->unsignedInteger('match_number')->nullable();
            $table->string('stage', 30)->nullable();
            $table->string('group_name', 20)->nullable();
            $table->string('status', 30)->nullable();
            $table->string('status_short', 10)->nullable();
            $table->string('status_label')->nullable();
            $table->string('venue')->nullable();
            $table->date('match_date')->nullable();
            $table->time('match_time')->nullable();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->unsignedTinyInteger('home_possession')->nullable();
            $table->unsignedTinyInteger('away_possession')->nullable();

            $table->json('goals_feed')->nullable();
            $table->json('events')->nullable();
            $table->json('statistics')->nullable();
            $table->json('lineups')->nullable();
            $table->json('players')->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('collected_at')->nullable()->index();
            $table->timestamps();

            $table->index(['tournament_id', 'match_number']);
            $table->index(['game_id', 'collected_at']);
            $table->index(['api_fixture_id', 'collected_at']);
            $table->index(['status', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_histories');
    }
};
