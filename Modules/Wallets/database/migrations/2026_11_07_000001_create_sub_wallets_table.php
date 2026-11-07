<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_wallet_id')->constrained('parent_wallets')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 9);
            $table->string('icon', 50);
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('reserved_balance', 18, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['parent_wallet_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_wallets');
    }
};
