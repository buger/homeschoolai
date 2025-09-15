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
            // Add unified learning content field (replaces description + learning_materials)
            $table->longText('learning_content')->nullable()->after('description');

            // Add assets tracking for file management
            $table->json('content_assets')->nullable()->after('learning_content');

            // Add migration status tracking
            $table->boolean('migrated_to_unified')->default(false)->after('content_assets');

            // Add index for migration queries
            $table->index(['migrated_to_unified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex(['migrated_to_unified']);
            $table->dropColumn(['learning_content', 'content_assets', 'migrated_to_unified']);
        });
    }
};
