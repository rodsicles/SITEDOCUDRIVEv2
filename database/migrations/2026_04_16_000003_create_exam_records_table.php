<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('folder_id');
            $table->string('exam_type', 100);
            $table->string('batch_label', 50);
            $table->unsignedInteger('passed_count');
            $table->unsignedInteger('total_examinees')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedBigInteger('document_id')->nullable();
            $table->timestamps();

            $table->foreign('folder_id')->references('folder_id')->on('folders')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('document_id')->references('document_id')->on('documents')->nullOnDelete();

            $table->index(['folder_id', 'exam_type']);
            $table->index('batch_label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_records');
    }
};
