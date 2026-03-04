<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id('announcement_id');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->enum('visibility', ['All', 'Dean', 'Program Coordinator', 'Faculty Employee'])->default('All');
            $table->enum('department', ['All', 'Engineering', 'Information Technology'])->default('All');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['is_pinned', 'created_at']);
            $table->index('visibility');
            $table->index('expires_at');
        });

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements', 'announcement_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['announcement_id', 'user_id']);
        });

        Schema::create('announcement_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements', 'announcement_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('emoji', 20);
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id', 'emoji']);
            $table->index('announcement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reactions');
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
