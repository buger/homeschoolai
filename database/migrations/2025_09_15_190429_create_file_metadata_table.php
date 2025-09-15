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
        Schema::create('file_metadata', function (Blueprint $table) {
            $table->id();
            $table->uuid('file_uuid')->unique();
            $table->string('original_name');
            $table->string('secure_name');
            $table->string('storage_path');
            $table->string('public_url')->nullable();
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->string('file_extension', 10);
            $table->string('file_category', 50); // images, documents, videos, audio, archives
            $table->string('file_subcategory', 50)->nullable();

            // File hashes for integrity and duplicate detection
            $table->string('hash_md5', 32);
            $table->string('hash_sha256', 64);
            $table->string('hash_crc32', 8)->nullable();

            // Educational content classification
            $table->foreignId('topic_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('educational_level', 50)->nullable(); // elementary, middle, high, college
            $table->string('content_type', 50)->nullable(); // worksheet, reference, tutorial, etc.
            $table->string('content_rating', 20)->default('general'); // general, children, teens, mature

            // Organization and structure
            $table->json('organization_metadata')->nullable(); // category, subcategory, tags
            $table->json('access_control')->nullable(); // permissions, restrictions, sharing settings
            $table->json('version_info')->nullable(); // version number, previous versions, change history

            // Security validation results
            $table->json('security_validation')->nullable(); // validation results, risk level, checks performed
            $table->json('threat_analysis')->nullable(); // threat detection results, risk score
            $table->json('integrity_check')->nullable(); // file integrity validation results

            // Optimization and processing
            $table->json('optimization_data')->nullable(); // compression ratios, thumbnails, previews
            $table->json('thumbnails')->nullable(); // generated thumbnail metadata
            $table->json('previews')->nullable(); // document/video preview metadata

            // Usage tracking
            $table->integer('download_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('last_modified_at')->nullable();

            // Flags and status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_quarantined')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('has_been_scanned')->default(false);

            $table->timestamps();

            // Indexes for performance
            $table->index(['topic_id', 'is_active']);
            $table->index(['subject_id', 'is_active']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index(['file_category', 'file_subcategory']);
            $table->index(['hash_md5']); // For duplicate detection
            $table->index(['hash_sha256']); // For security scanning
            $table->index(['is_quarantined', 'has_been_scanned']);
            $table->index(['content_rating', 'educational_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_metadata');
    }
};
