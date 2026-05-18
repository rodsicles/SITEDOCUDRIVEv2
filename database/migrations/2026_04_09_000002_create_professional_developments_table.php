<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_developments', function (Blueprint $table) {
            $table->id('professional_development_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('seminar_name', 150);
            $table->date('date_attended');
            $table->string('organizer', 150);
            $table->decimal('hours', 5, 1);
            $table->string('certificate_path')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('date_attended');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_developments');
    }
};
