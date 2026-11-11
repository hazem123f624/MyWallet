<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Wallets\Enums\ParentWalletStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('currency', 10)->default('EGP');
            $table->string('currency_symbol', 16)->nullable();
            $table->decimal('total_allocated_balance', 18, 2)->default(0);
            $table->decimal('available_balance', 18, 2)->default(0);
            $table->decimal('reserved_balance', 18, 2)->default(0);
            $table->string('status', 20)->default(ParentWalletStatus::Unlinked->value)->index();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();
        });

        $users = DB::table('users')->whereNull('deleted_at')->get(['id', 'country_id']);
        $countries = DB::table('settings')
            ->where('type', 'country')
            ->get(['id', 'data'])
            ->keyBy('id');

        $timestamp = now();
        $rows = [];

        foreach ($users as $user) {
            $country = $user->country_id ? $countries->get($user->country_id) : null;
            $countryData = json_decode((string) ($country->data ?? ''), true) ?: [];
            $currency = $countryData['currency'] ?? config('wallets.default_currency', 'EGP');
            $symbol = $countryData['currency_symbol'] ?? config('wallets.default_currency_symbol', 'E£');

            $rows[] = [
                'user_id' => $user->id,
                'bank_account_id' => null,
                'currency' => $currency,
                'currency_symbol' => $symbol,
                'total_allocated_balance' => 0,
                'available_balance' => 0,
                'reserved_balance' => 0,
                'status' => ParentWalletStatus::Unlinked->value,
                'linked_at' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (count($rows) >= 500) {
                DB::table('parent_wallets')->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('parent_wallets')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_wallets');
    }
};
