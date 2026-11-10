<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_wallet_id')->constrained('parent_wallets')->cascadeOnDelete();
            $table->foreignId('sub_wallet_id')->constrained('sub_wallets')->restrictOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('client_reference')->nullable();
            $table->string('sub_wallet_name');
            $table->string('beneficiary_name');
            $table->string('beneficiary_bank_name');
            $table->string('beneficiary_account_last4', 4);
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10);
            $table->string('status', 20)->default('pending')->index();
            $table->string('provider_name')->nullable();
            $table->string('provider_reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'client_reference'], 'transfers_user_client_reference_unique');
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
