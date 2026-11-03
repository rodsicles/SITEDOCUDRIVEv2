<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_reset_events', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 30)->index();
            $table->timestamp('reset_at')->index();
            $table->json('closed_metrics')->nullable();
            $table->foreignId('reset_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_reset_events');
    }
};
