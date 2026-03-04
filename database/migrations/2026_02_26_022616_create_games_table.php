<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {

            $table->id();

            $table->foreignId('tournament_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('match_number');

            $table->foreignId('home_team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            $table->foreignId('away_team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            $table->enum('stage', [
                'group',
                'round_32',
                'round_16',
                'quarter',
                'semi',
                'third_place',
                'final'
            ])->default('group');

            // NUEVO: grupo (A-L)
            $table->string('group')->nullable();

            // ciudad
            $table->string('venue')->nullable();

            $table->dateTime('match_date');

            // RESULTADOS
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();

            // ESTADO DEL PARTIDO
            $table->enum('status', [
                'scheduled',
                'live',
                'finished'
            ])->default('scheduled');

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEXES
            |--------------------------------------------------------------------------
            */

            $table->index(['tournament_id', 'stage']);
            $table->index(['match_number']);
            $table->index(['home_team_id']);
            $table->index(['away_team_id']);
            $table->index(['group']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
