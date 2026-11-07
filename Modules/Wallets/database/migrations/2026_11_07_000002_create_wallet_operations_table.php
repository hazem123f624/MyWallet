<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_wallet_id')->constrained('parent_wallets')->cascadeOnDelete();
            $table->foreignId('from_sub_wallet_id')->nullable()->constrained('sub_wallets')->nullOnDelete();
            $table->foreignId('to_sub_wallet_id')->nullable()->constrained('sub_wallets')->nullOnDelete();
            $table->string('type', 30)->index();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10);
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['parent_wallet_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_operations');
    }
};
