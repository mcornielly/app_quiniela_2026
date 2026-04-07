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
        Schema::table('countries', function (Blueprint $table) {
            $table->string('api_name')->nullable()->after('name');
            $table->string('api_flag_url')->nullable()->after('flag_path');
            
            $table->index('api_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropIndex(['api_name']);
            $table->dropColumn(['api_name', 'api_flag_url']);
        });
    }
};
