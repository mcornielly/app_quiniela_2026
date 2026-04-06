<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            if (!Schema::hasColumn('notifications', 'hidden_at')) {
                $table->timestamp('hidden_at')->nullable()->after('read_at')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications') || !Schema::hasColumn('notifications', 'hidden_at')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            try {
                $table->dropIndex('notifications_hidden_at_index');
            } catch (\Throwable $e) {
                // Index may not exist in some sqlite rebuild scenarios.
            }

            $table->dropColumn('hidden_at');
        });
    }
};
