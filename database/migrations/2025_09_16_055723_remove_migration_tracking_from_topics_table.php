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
        Schema::table('topics', function (Blueprint $table) {
            // Remove migration tracking fields that are no longer needed
            if (Schema::hasColumn('topics', 'migrated_to_unified')) {
                $table->dropIndex(['migrated_to_unified']);
                $table->dropColumn('migrated_to_unified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Re-add migration tracking field if needed to rollback
            $table->boolean('migrated_to_unified')->default(false)->after('content_assets');
            $table->index(['migrated_to_unified']);
        });
    }
};
