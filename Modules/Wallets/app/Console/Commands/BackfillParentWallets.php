<?php

namespace Modules\Wallets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Wallets\Services\ParentWalletService;

class BackfillParentWallets extends Command
{
    protected $signature = 'wallets:backfill';

    protected $description = 'Create missing Parent Wallets for existing users';

    public function handle(ParentWalletService $service): int
    {
        $created = 0;

        DB::table('users')
            ->whereNull('deleted_at')
            ->select(['id', 'country_id'])
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($service, &$created): void {
                foreach ($users as $user) {
                    $existing = DB::table('parent_wallets')->where('user_id', $user->id)->exists();

                    if ($existing) {
                        continue;
                    }

                    $service->ensureForUser((int) $user->id, $user->country_id ? (int) $user->country_id : null);
                    $created++;
                }
            });

        $this->info("Parent Wallet backfill complete. Created: {$created}.");

        return self::SUCCESS;
    }
}
