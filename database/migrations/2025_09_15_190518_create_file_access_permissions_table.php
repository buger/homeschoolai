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
        Schema::create('file_access_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_metadata_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('role', 50)->nullable(); // For role-based permissions

            // Permission levels
            $table->string('permission_level', 20); // none, view, download, edit, delete, admin
            $table->json('specific_permissions')->nullable(); // specific actions allowed
            $table->boolean('can_share')->default(false);
            $table->boolean('can_modify_permissions')->default(false);

            // Access scope and restrictions
            $table->string('access_scope', 50)->default('private'); // private, family, topic, subject, public, restricted
            $table->json('access_restrictions')->nullable(); // time, location, device restrictions
            $table->json('conditional_requirements')->nullable(); // prerequisites, approvals needed

            // Time-based restrictions
            $table->string('time_restriction', 50)->default('always'); // always, school_hours, study_time, supervised, scheduled
            $table->json('time_schedule')->nullable(); // specific time slots when access is allowed
            $table->timestamp('access_valid_from')->nullable();
            $table->timestamp('access_valid_until')->nullable();

            // Geographic restrictions
            $table->string('geo_restriction', 50)->default('none'); // none, home, school, safe_zones, country
            $table->json('allowed_locations')->nullable(); // specific allowed locations
            $table->json('blocked_locations')->nullable(); // specific blocked locations

            // Usage limitations
            $table->integer('max_downloads')->nullable(); // maximum downloads allowed
            $table->integer('max_views')->nullable(); // maximum views allowed
            $table->integer('current_downloads')->default(0);
            $table->integer('current_views')->default(0);
            $table->integer('daily_download_limit')->nullable();
            $table->integer('daily_view_limit')->nullable();

            // Approval workflow
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Sharing settings
            $table->json('sharing_restrictions')->nullable(); // no_sharing, family_only, supervised_sharing
            $table->boolean('allow_public_sharing')->default(false);
            $table->boolean('allow_link_sharing')->default(false);
            $table->integer('max_share_recipients')->nullable();

            // Content filtering
            $table->string('content_filter_level', 20)->default('standard'); // strict, standard, relaxed
            $table->json('blocked_content_types')->nullable();
            $table->json('allowed_content_types')->nullable();

            // Audit trail
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Unique constraints and indexes
            $table->unique(['file_metadata_id', 'user_id'], 'unique_file_user_permission');
            $table->unique(['file_metadata_id', 'role'], 'unique_file_role_permission');

            $table->index(['file_metadata_id', 'permission_level']);
            $table->index(['user_id', 'permission_level', 'is_active']);
            $table->index(['role', 'permission_level', 'is_active']);
            $table->index(['access_scope', 'is_active']);
            $table->index(['requires_approval', 'approved_at']);
            $table->index(['access_valid_from', 'access_valid_until']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_access_permissions');
    }
};
