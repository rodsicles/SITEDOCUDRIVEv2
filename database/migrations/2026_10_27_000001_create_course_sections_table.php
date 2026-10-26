<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->string('program', 20);
            $table->unsignedTinyInteger('year_level');
            $table->string('name', 40);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamps();
            $table->unique(['program', 'name']);
        });
        foreach (range(1, 4) as $year) {
            foreach (['A', 'B'] as $letter) {
                DB::table('course_sections')->insert([
                    'program' => 'BSIT', 'year_level' => $year, 'name' => "BSIT $year $letter",
                    'is_active' => true, 'lock_version' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sections');
    }
};
