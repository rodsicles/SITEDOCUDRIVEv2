<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('is_system')->index();
            $table->foreignId('privacy_owner_id')->nullable()->after('is_private')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('privacy_owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['privacy_owner_id']);
            $table->dropColumn(['is_private', 'privacy_owner_id', 'locked_at']);
        });
    }
};
