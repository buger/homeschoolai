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
            $table->text('description')->nullable()->after('title');
            $table->json('learning_materials')->nullable()->after('description');

            // PostgreSQL doesn't support indexing JSON directly, so we'll use GIN index for JSON queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn(['description', 'learning_materials']);
        });
    }
};
