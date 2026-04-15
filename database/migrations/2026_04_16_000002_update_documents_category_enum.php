<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Map existing category values to new ones
        DB::table('documents')->where('category', 'Certificates')->update(['category' => 'Other']);
        DB::table('documents')->where('category', 'Forms')->update(['category' => 'Other']);
        DB::table('documents')->where('category', 'Memos')->update(['category' => 'Other']);
        DB::table('documents')->where('category', 'Reports')->update(['category' => 'Other']);
        DB::table('documents')->where('category', 'Policies')->update(['category' => 'Other']);
        DB::table('documents')->where('category', 'Research Papers')->update(['category' => 'Other']);

        // Change enum values
        DB::statement("ALTER TABLE documents MODIFY COLUMN category ENUM('Accreditation and Certifications', 'Academics', 'Other') DEFAULT 'Other'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN category ENUM('Policies', 'Forms', 'Reports', 'Memos', 'Research Papers', 'Other') DEFAULT 'Other'");
    }
};
