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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('world_cup_group_id')->nullable()->constrained('world_cup_groups')->nullOnDelete();

            $table->string('name');
            $table->string('short_code', 8)->nullable(); // MEX, USA, etc.
            $table->string('flag_url')->nullable(); // opcional (si luego usas API)
            $table->boolean('is_placeholder')->default(false); // repechajes
            $table->timestamps();

            $table->unique(['tournament_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
