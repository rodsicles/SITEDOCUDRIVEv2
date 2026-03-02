<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id('folder_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('folder_name', 100);
            $table->string('color', 7)->default('#028a0f');
            $table->timestamps();
            
            // Ensure unique folder names per user
            $table->unique(['user_id', 'folder_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
