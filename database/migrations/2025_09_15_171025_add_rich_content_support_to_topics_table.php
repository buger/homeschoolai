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
            // Convert description from text to longtext for rich content
            $table->longText('description')->nullable()->change();

            // Add content format tracking (enum: 'plain', 'markdown', 'html')
            $table->enum('content_format', ['plain', 'markdown', 'html'])->default('plain')->after('description');

            // Add metadata for rich content (word count, reading time, etc.)
            $table->json('content_metadata')->nullable()->after('content_format');

            // Add embedded images tracking
            $table->json('embedded_images')->nullable()->after('content_metadata');

            // Index for content format searches
            $table->index(['content_format']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['content_format', 'content_metadata', 'embedded_images']);

            // Revert description back to text (PostgreSQL handles this gracefully)
            $table->text('description')->nullable()->change();

            // Drop index
            $table->dropIndex(['content_format']);
        });
    }
};
