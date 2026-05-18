<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_guides', function (Blueprint $table) {
            if (!Schema::hasColumn('teaching_guides', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('academic_year');
            }
            if (!Schema::hasColumn('teaching_guides', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('teaching_guides', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('teaching_guides', 'remarks')) {
                $table->string('remarks', 500)->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teaching_guides', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['status', 'reviewed_by', 'reviewed_at', 'remarks']);
        });
    }
};
