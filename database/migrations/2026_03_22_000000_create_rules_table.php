<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->default('Regla general de quiniela');
            $table->dateTime('tournament_starts_at')->nullable();
            $table->dateTime('participation_closes_at')->nullable();
            $table->unsignedTinyInteger('exact_score_points')->default(5);
            $table->unsignedTinyInteger('correct_result_points')->default(3);
            $table->enum('unpaid_after_window_action', ['locked', 'cancelled'])->default('locked');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tournament_id']);
            $table->index(['active', 'participation_closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};

