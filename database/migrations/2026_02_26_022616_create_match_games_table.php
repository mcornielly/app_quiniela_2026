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
        Schema::create('match_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('world_cup_group_id')->nullable()->constrained('world_cup_groups')->nullOnDelete();

            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();

            $table->timestamp('starts_at')->nullable();
            $table->string('stage')->default('group'); // group|r16|qf|sf|third|final
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();

            $table->string('status')->default('scheduled'); // scheduled|live|finished
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index(['tournament_id', 'stage', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_games');
    }
};
