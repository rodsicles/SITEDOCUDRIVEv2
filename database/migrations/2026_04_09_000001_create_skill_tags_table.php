<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_tags', function (Blueprint $table) {
            $table->id('skill_tag_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tag_name', 50);
            $table->timestamps();

            $table->unique(['user_id', 'tag_name']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_tags');
    }
};
