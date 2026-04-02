<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stadiums', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_venue_id')->nullable()->unique();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('country', 120)->nullable();
            $table->string('address')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('surface', 120)->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stadiums');
    }
};
