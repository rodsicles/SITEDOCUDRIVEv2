<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name', 100);
            $table->string('color', 20)->default('#028a0f');
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('document_type');
            $table->text('tags')->nullable()->after('category_id');
            $table->foreign('category_id')->references('category_id')->on('document_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'tags']);
        });
        
        Schema::dropIfExists('document_categories');
    }
};
