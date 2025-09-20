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
        Schema::create('file_security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('file_metadata_id')->nullable()->constrained('file_metadata')->onDelete('cascade');

            // Event information
            $table->string('event_type', 50); // upload_attempt, validation, access_attempt, download, etc.
            $table->string('event_action', 50); // create, read, update, delete, scan, block
            $table->string('event_status', 20); // success, failure, blocked, quarantined
            $table->text('event_description')->nullable();

            // Security-specific data
            $table->string('risk_level', 20)->nullable(); // minimal, low, medium, high, critical
            $table->json('security_checks')->nullable(); // results of various security checks
            $table->json('threat_indicators')->nullable(); // detected threats or suspicious patterns
            $table->json('validation_results')->nullable(); // file validation outcomes

            // Request context
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->string('request_id', 255)->nullable();

            // File context
            $table->string('original_filename')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();

            // Access control context
            $table->string('permission_level', 20)->nullable();
            $table->json('access_restrictions')->nullable();
            $table->boolean('access_granted')->default(false);
            $table->text('access_denial_reason')->nullable();

            // Geographic and temporal context
            $table->string('country_code', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->json('location_data')->nullable();
            $table->time('access_time')->nullable();
            $table->date('access_date')->nullable();

            // Response and outcomes
            $table->integer('response_time_ms')->nullable();
            $table->json('response_metadata')->nullable();
            $table->boolean('requires_review')->default(false);
            $table->boolean('automated_action_taken')->default(false);
            $table->text('automated_action_details')->nullable();

            $table->timestamps();

            // Indexes for security analysis and performance
            $table->index(['user_id', 'event_type', 'created_at']);
            $table->index(['event_type', 'event_status', 'created_at']);
            $table->index(['risk_level', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['access_granted', 'requires_review']);
            $table->index(['file_hash']); // For tracking file-specific security events
            $table->index(['country_code', 'created_at']);
            $table->index(['access_date', 'access_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_security_logs');
    }
};
