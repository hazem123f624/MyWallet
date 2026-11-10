<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table): void {
            $table->timestamp('last_checked_at')->nullable()->after('completed_at');
            $table->timestamp('next_check_at')->nullable()->after('last_checked_at')->index();
            $table->unsignedSmallInteger('check_attempts')->default(0)->after('next_check_at');
            $table->timestamp('reversed_at')->nullable()->after('check_attempts');
            $table->timestamp('refunded_at')->nullable()->after('reversed_at');
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table): void {
            $table->dropColumn(['last_checked_at', 'next_check_at', 'check_attempts', 'reversed_at', 'refunded_at']);
        });
    }
};
