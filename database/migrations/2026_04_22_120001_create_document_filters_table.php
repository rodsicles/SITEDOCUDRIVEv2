<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_filters', function (Blueprint $table) {
            $table->id('document_filter_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 50);
            $table->json('filters');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_filters');
    }
};