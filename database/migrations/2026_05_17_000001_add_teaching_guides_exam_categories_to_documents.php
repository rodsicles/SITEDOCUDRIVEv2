<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN category ENUM(
            'Accreditation and Certifications',
            'Academics',
            'Teaching Guides',
            'Exam Questionnaires',
            'Other'
        ) DEFAULT 'Other'");
    }

    public function down(): void
    {
        DB::table('documents')->where('category', 'Teaching Guides')->update(['category' => 'Other']);
        DB::table('documents')->where('category', 'Exam Questionnaires')->update(['category' => 'Other']);

        DB::statement("ALTER TABLE documents MODIFY COLUMN category ENUM(
            'Accreditation and Certifications',
            'Academics',
            'Other'
        ) DEFAULT 'Other'");
    }
};
