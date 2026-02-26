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
        Schema::table('match_games', function (Blueprint $table) {
            // Para filtrar rápido por grupos en fase de grupos (A-L)
            $table->string('group_code', 2)->nullable()->after('world_cup_group_id');

            // Ciudad/Sede (CD. MEXICO, GUADALAJARA, MIAMI, etc.)
            $table->string('city', 80)->nullable()->after('group_code');

            // Para eliminatorias: referencias como "2A", "1E", "3ABCDF", etc.
            // (cuando aún no se conocen los equipos reales)
            $table->string('home_ref', 20)->nullable()->after('away_team_id');
            $table->string('away_ref', 20)->nullable()->after('home_ref');

            // Opcional pero recomendado: saber en qué zona horaria se cargó el schedule
            $table->string('timezone', 50)->default('America/Caracas')->after('starts_at');

            $table->index(['tournament_id', 'stage']);
            $table->index(['tournament_id', 'group_code']);
        });
    }

    public function down(): void
    {
        Schema::table('match_games', function (Blueprint $table) {
            $table->dropIndex(['tournament_id', 'stage']);
            $table->dropIndex(['tournament_id', 'group_code']);

            $table->dropColumn(['group_code', 'city', 'home_ref', 'away_ref', 'timezone']);
        });
    }
};
