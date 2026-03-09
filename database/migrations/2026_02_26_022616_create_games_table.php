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
        Schema::create('games', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Tournament relation
            |--------------------------------------------------------------------------
            */

            $table->foreignId('tournament_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Match identity
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('match_number')->unique();

            /*
            |--------------------------------------------------------------------------
            | Teams (when known)
            |--------------------------------------------------------------------------
            */

            $table->foreignId('home_team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            $table->foreignId('away_team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Slot references (when teams are not yet defined)
            |--------------------------------------------------------------------------
            |
            | Examples:
            | 1A
            | 2B
            | 3-ABCDF
            | W74
            | RU101
            |
            */

            $table->string('home_slot', 20)->nullable();
            $table->string('away_slot', 20)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Match results
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();

            $table->foreignId('winner_team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();
            $table->string('result_type', 10)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Game status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'scheduled',
                'in_progress',
                'finished'
            ])->default('scheduled');

            /*
            |--------------------------------------------------------------------------
            | Tournament stage
            |--------------------------------------------------------------------------
            */

            $table->enum('stage', [
                'group',
                'round_32',
                'round_16',
                'quarter',
                'semi',
                'third_place',
                'final'
            ])->default('group');

            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */

            $table->string('venue')->nullable();
            $table->date('match_date');
            $table->time('match_time')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(['tournament_id', 'stage']);
            $table->index('match_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
