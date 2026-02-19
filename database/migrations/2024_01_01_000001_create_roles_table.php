<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('role_name', 50)->unique();
            $table->timestamps();
        });

        // Insert default roles
        DB::table('roles')->insert([
            ['role_name' => 'Dean'],
            ['role_name' => 'Program Coordinator'],
            ['role_name' => 'Faculty Employee'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
