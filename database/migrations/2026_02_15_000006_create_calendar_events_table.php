<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id('event_id');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('event_type', ['Meeting', 'Deadline', 'Training', 'Conference', 'Holiday', 'Seminar', 'Other'])->default('Other');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('location')->nullable();
            $table->boolean('all_day')->default(false);
            $table->enum('visibility', ['Public', 'Department', 'Private'])->default('Public');
            $table->boolean('send_reminder')->default(true);
            $table->integer('reminder_minutes')->default(30); // Minutes before event
            $table->timestamps();
            
            $table->index(['start_datetime', 'end_datetime']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
