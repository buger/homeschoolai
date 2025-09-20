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
            // Add unified learning content field
            $table->longText('learning_content')->nullable()->after('description');

            // Add assets tracking for file management
            $table->json('content_assets')->nullable()->after('learning_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn(['learning_content', 'content_assets']);
        });
    }
};
