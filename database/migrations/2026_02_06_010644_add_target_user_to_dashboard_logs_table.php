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
        Schema::table('dashboard_logs', function (Blueprint $table) {
            $table->foreignId('target_user_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null')->comment('User affected by this action');
            $table->string('activity_type', 50)->nullable()->after('activity')->comment('Type: document_upload, password_reset, profile_update, login, logout');
            $table->string('visibility', 50)->default('own')->after('activity_type')->comment('Visibility: own, coordinator, dean, all');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard_logs', function (Blueprint $table) {
            $table->dropForeign(['target_user_id']);
            $table->dropColumn(['target_user_id', 'activity_type', 'visibility']);
        });
    }
};
