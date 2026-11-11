<?php

namespace Modules\Transfers\Console\Commands;

use Illuminate\Console\Command;
use Modules\Transfers\Services\TransferLifecycleService;

class ReconcileTransfers extends Command
{
    protected $signature = 'transfers:reconcile {--limit=50}';

    protected $description = 'Query the banking provider for due pending and processing transfers';

    public function handle(TransferLifecycleService $lifecycle): int
    {
        $checked = $lifecycle->reconcileDue(max(1, (int) $this->option('limit')));

        $this->info("Transfer reconciliation complete. Checked: {$checked}.");

        return self::SUCCESS;
    }
}
