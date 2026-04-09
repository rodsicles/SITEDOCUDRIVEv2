<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_items', function (Blueprint $table) {
            $table->id('equipment_item_id');
            $table->string('item_name', 100);
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->enum('status', ['Available', 'Unavailable'])->default('Available');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_items');
    }
};
