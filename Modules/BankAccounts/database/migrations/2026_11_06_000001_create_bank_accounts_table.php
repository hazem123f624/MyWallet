<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\BankAccounts\Enums\BankAccountStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained('banks')->restrictOnDelete();
            $table->string('account_holder_name');
            $table->text('account_number');
            $table->string('account_number_hash', 64);
            $table->string('account_number_last4', 4);
            $table->text('iban')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->string('status', 20)->default(BankAccountStatus::Pending->value)->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'bank_id', 'account_number_hash'],
                'bank_accounts_owner_account_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
