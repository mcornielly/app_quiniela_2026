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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_player_id')->nullable()->unique();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->integer('age')->nullable();
            $table->string('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->string('position')->nullable(); // goalkeeper, defender, midfielder, attacker
            $table->integer('number')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('local_photo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
