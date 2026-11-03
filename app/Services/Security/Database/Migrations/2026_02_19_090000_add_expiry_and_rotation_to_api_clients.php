<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('is_active');
            $table->timestamp('last_rotated_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'last_rotated_at']);
        });
    }
};
