<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('year');
            $table->decimal('sick_leave_balance', 5, 1)->default(15.0);
            $table->decimal('vacation_leave_balance', 5, 1)->default(15.0);
            $table->decimal('sick_leave_used', 5, 1)->default(0);
            $table->decimal('vacation_leave_used', 5, 1)->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
