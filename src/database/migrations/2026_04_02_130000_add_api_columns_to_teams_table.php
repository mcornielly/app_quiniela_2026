<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('api_team_id')->nullable()->after('shield_path');
            $table->string('api_team_logo_url')->nullable()->after('api_team_id');

            $table->index('api_team_id');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['api_team_id']);
            $table->dropColumn(['api_team_id', 'api_team_logo_url']);
        });
    }
};
