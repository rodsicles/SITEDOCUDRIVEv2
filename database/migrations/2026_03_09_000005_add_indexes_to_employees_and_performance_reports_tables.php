<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('department');
            $table->index(['user_id', 'department']);
        });

        Schema::table('performance_reports', function (Blueprint $table) {
            $table->index(['employee_id', 'report_date']);
            $table->index(['report_date', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['department']);
            $table->dropIndex(['user_id', 'department']);
        });

        Schema::table('performance_reports', function (Blueprint $table) {
            $table->dropIndex(['employee_id', 'report_date']);
            $table->dropIndex(['report_date', 'rating']);
        });
    }
};
