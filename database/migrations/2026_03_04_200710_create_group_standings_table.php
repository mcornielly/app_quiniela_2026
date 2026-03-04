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
        Schema::create('group_standings', function (Blueprint $table) {

            $table->id();

            $table->foreignId('tournament_id')->constrained();
            $table->foreignId('group_id')->constrained();
            $table->foreignId('team_id')->constrained();

            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('wins')->default(0);
            $table->unsignedSmallInteger('draws')->default(0);
            $table->unsignedSmallInteger('losses')->default(0);

            $table->unsignedSmallInteger('gf')->default(0);
            $table->unsignedSmallInteger('ga')->default(0);

            $table->smallInteger('gd')->default(0);

            $table->unsignedSmallInteger('points')->default(0);

            $table->unsignedSmallInteger('position')->nullable();

            $table->string('form', 10)->nullable();

            $table->unique(['tournament_id','group_id','team_id']);

            $table->index(['group_id','position']);
            $table->index(['tournament_id','points','gd']);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_standings');
    }
};
