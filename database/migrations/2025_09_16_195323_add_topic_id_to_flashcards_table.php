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
        Schema::table('flashcards', function (Blueprint $table) {
            // Add topic_id column after unit_id for logical ordering
            $table->foreignId('topic_id')->nullable()->after('unit_id');

            // Add foreign key constraint to topics table with cascade delete
            $table->foreign('topic_id')->references('id')->on('topics')->onDelete('cascade');

            // Add index on topic_id for performance optimization
            $table->index(['topic_id']);

            // Add composite index for topic_id with commonly queried fields
            $table->index(['topic_id', 'is_active', 'created_at'], 'flashcards_topic_active_created_idx');
            $table->index(['topic_id', 'card_type', 'is_active'], 'flashcards_topic_type_active_idx');
            $table->index(['topic_id', 'difficulty_level', 'is_active'], 'flashcards_topic_difficulty_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration is designed to move forward only.
     * The down method is provided for completeness but should not be used
     * in production as it would break the new flashcard → topic relationship.
     */
    public function down(): void
    {
        Schema::table('flashcards', function (Blueprint $table) {
            // Drop composite indexes first
            $table->dropIndex('flashcards_topic_active_created_idx');
            $table->dropIndex('flashcards_topic_type_active_idx');
            $table->dropIndex('flashcards_topic_difficulty_active_idx');

            // Drop individual index
            $table->dropIndex(['topic_id']);

            // Drop foreign key constraint
            $table->dropForeign(['topic_id']);

            // Drop the column
            $table->dropColumn('topic_id');
        });
    }
};
