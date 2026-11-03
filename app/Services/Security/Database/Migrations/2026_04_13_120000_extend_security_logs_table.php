<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('risk_score')->nullable()->after('details');
            $table->string('fingerprint', 64)->nullable()->index()->after('risk_score');
            $table->string('nonce', 64)->nullable()->index()->after('fingerprint');
            $table->json('flags')->nullable()->after('nonce');
            $table->string('user_agent', 512)->nullable()->after('flags');
            $table->string('request_method', 10)->nullable()->after('user_agent');
            $table->string('request_path', 2048)->nullable()->after('request_method');
            $table->unsignedBigInteger('api_client_id')->nullable()->after('request_path');

            $table->foreign('api_client_id')
                ->references('id')
                ->on('api_clients')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('security_logs', function (Blueprint $table) {
            $table->dropForeign(['api_client_id']);
            $table->dropIndex(['fingerprint']);
            $table->dropIndex(['nonce']);
            $table->dropColumn([
                'risk_score',
                'fingerprint',
                'nonce',
                'flags',
                'user_agent',
                'request_method',
                'request_path',
                'api_client_id',
            ]);
        });
    }
};
