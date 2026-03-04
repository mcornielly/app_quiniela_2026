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

            $table->foreignId('pool_entry_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();

            $table->integer('points')->default(0);

            $table->timestamps();

            $table->unique(['pool_entry_id', 'game_id']);

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
