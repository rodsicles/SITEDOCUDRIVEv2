<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            // Add hierarchy support
            $table->unsignedBigInteger('parent_id')->nullable()->after('folder_id');
            $table->boolean('is_system')->default(false)->after('color');
            $table->tinyInteger('level')->default(0)->after('is_system');
            $table->integer('sort_order')->default(0)->after('level');
            $table->string('slug', 100)->nullable()->unique()->after('sort_order');

            // Foreign key for parent
            $table->foreign('parent_id')->references('folder_id')->on('folders')->onDelete('cascade');
        });

        // Make user_id nullable for system folders
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'folder_name']);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('folder_name', 150)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_system', 'level', 'sort_order', 'slug']);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->string('folder_name', 100)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'folder_name']);
        });
    }
};
