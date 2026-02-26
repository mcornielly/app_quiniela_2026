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
        Schema::create('pool_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // "Mi Quiniela #1"
            $table->string('status')->default('draft'); // draft|complete|paid_locked|live|finished
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->unsignedInteger('total_points')->default(0);

            $table->timestamp('paid_at')->nullable();
            $table->string('payment_ref')->nullable();

            $table->timestamps();

            $table->index(['tournament_id', 'status']);
            $table->index(['user_id', 'tournament_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_entries');
    }
};
