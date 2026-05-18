<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status');
            $table->index('due_date');
            $table->index(['assigned_to', 'status']);
            $table->index(['assigned_by', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['assigned_to', 'status']);
            $table->dropIndex(['assigned_by', 'status']);
            $table->dropIndex(['status', 'due_date']);
        });
    }
};
