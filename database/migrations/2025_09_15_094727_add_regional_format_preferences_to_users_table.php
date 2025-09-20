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
        Schema::table('users', function (Blueprint $table) {
            // Regional format preset: 'us', 'eu', or 'custom'
            $table->string('region_format', 10)->default('us');

            // Time format preference: '12h' or '24h'
            $table->string('time_format', 3)->default('12h');

            // Week start preference: 'sunday' or 'monday'
            $table->string('week_start', 10)->default('sunday');

            // Date format type: 'us', 'eu', or 'iso'
            $table->string('date_format_type', 10)->default('us');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'region_format',
                'time_format',
                'week_start',
                'date_format_type',
            ]);
        });
    }
};
