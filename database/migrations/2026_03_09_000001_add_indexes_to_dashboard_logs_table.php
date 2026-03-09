<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_logs', function (Blueprint $table) {
            $table->index('activity_type');
            $table->index('log_date');
            $table->index('visibility');
            $table->index(['user_id', 'log_date']);
            $table->index(['activity_type', 'log_date']);
            $table->index(['visibility', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_logs', function (Blueprint $table) {
            $table->dropIndex(['activity_type']);
            $table->dropIndex(['log_date']);
            $table->dropIndex(['visibility']);
            $table->dropIndex(['user_id', 'log_date']);
            $table->dropIndex(['activity_type', 'log_date']);
            $table->dropIndex(['visibility', 'log_date']);
        });
    }
};
