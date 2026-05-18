<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20);
            $table->string('title', 150);
            $table->string('department', 50);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['code', 'department']);
            $table->index(['department', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
