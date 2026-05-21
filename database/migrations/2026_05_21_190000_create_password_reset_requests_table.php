<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_requests', function (Blueprint $table) {
            $table->id('password_reset_request_id');
            $table->foreignId('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'denied', 'expired'])
                ->default('pending');
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('expires_at');
            $table->string('requested_ip', 45)->nullable();
            $table->timestamps();

            $table->foreign('handled_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
