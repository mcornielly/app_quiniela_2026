<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedBigInteger('api_fixture_id')->nullable()->after('match_number');
            $table->foreignId('stadium_id')->nullable()->after('venue')->constrained('stadiums')->nullOnDelete();

            $table->index('api_fixture_id');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['api_fixture_id']);
            $table->dropConstrainedForeignId('stadium_id');
            $table->dropColumn(['api_fixture_id']);
        });
    }
};
