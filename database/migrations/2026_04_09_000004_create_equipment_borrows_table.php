<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_borrows', function (Blueprint $table) {
            $table->id('equipment_borrow_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('equipment_item_id');
            $table->text('purpose');
            $table->date('borrow_date');
            $table->time('borrow_time');
            $table->date('return_date');
            $table->time('return_time');
            $table->datetime('actual_return_date')->nullable();
            $table->enum('status', ['Borrowed', 'Returned'])->default('Borrowed');
            $table->timestamps();

            $table->foreign('equipment_item_id')
                  ->references('equipment_item_id')
                  ->on('equipment_items')
                  ->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index('equipment_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_borrows');
    }
};
