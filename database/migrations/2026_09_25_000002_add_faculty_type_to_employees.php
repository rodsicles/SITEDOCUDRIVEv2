<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('faculty_type', 20)->nullable()->after('program')->index();
        });

        DB::table('employees')->whereIn('user_id', DB::table('users')->where('role_id', 3)->select('id'))
            ->update(['faculty_type' => 'full_time']);
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('faculty_type'));
    }
};
