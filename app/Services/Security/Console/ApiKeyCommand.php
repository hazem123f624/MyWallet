<?php

namespace App\Services\Security\Console;

use App\Services\Security\Models\ApiClient;
use App\Services\Security\Services\ApiKeyService;
use Illuminate\Console\Command;

/**
 * Artisan command to manage API clients.
 *
 * Usage:
 *   php artisan security:api-key create "Flutter App"        → Create with 30-day expiry
 *   php artisan security:api-key create "Web App" --days=90  → Create with 90-day expiry
 *   php artisan security:api-key create "Server" --no-expiry → Create without expiration
 *   php artisan security:api-key rotate 3                    → Rotate keys for client ID 3
 *   php artisan security:api-key renew 3 --days=60           → Extend expiry by 60 days
 *   php artisan security:api-key list                        → List all clients
 *   php artisan security:api-key revoke 3                    → Deactivate client
 */
class ApiKeyCommand extends Command
{
    protected $signature = 'security:api-key
                            {action : create|rotate|renew|list|revoke}
                            {value? : Client name (create) or client ID (rotate/renew/revoke)}
                            {--days= : Expiry days (default: config value)}
                            {--no-expiry : Create key without expiration}';

    protected $description = 'Manage API client keys — create, rotate, renew, list, or revoke';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'create' => $this->createClient(),
            'rotate' => $this->rotateClient(),
            'renew' => $this->renewClient(),
            'list' => $this->listClients(),
            'revoke' => $this->revokeClient(),
            default => $this->error("Unknown action: {$this->argument('action')}") ?? 1,
        };
    }

    private function createClient(): int
    {
        $name = $this->argument('value');
        if (! $name) {
            $this->error('Client name is required: php artisan security:api-key create "My App"');

            return 1;
        }

        $days = $this->option('no-expiry') ? null : ($this->option('days') ?? null);
        $client = ApiKeyService::createClient($name, $days ? (int) $days : ($this->option('no-expiry') ? null : null));

        // If no-expiry flag is set, remove expiration
        if ($this->option('no-expiry')) {
            $client->update(['expires_at' => null]);
            $client->refresh();
        }

        $this->newLine();
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║  ✅ API Client Created Successfully      ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $client->name],
                ['API Key', $client->api_key],
                ['Secret', $client->secret],
                ['Expires At', $client->expires_at?->format('Y-m-d H:i') ?? 'Never'],
                ['Status', '🟢 Active'],
            ]
        );

        $this->newLine();
        $this->warn('⚠️  Save the API Key and Secret now — the Secret will NOT be shown again.');

        return 0;
    }

    private function rotateClient(): int
    {
        $client = $this->findClient();
        if (! $client) {
            return 1;
        }

        if (! $this->confirm("Rotate keys for \"{$client->name}\"? The old keys will immediately become invalid.")) {
            return 0;
        }

        $days = $this->option('days') ? (int) $this->option('days') : null;
        $client = ApiKeyService::rotateKeys($client, $days);

        $this->newLine();
        $this->info('🔄 Keys rotated successfully!');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $client->name],
                ['New API Key', $client->api_key],
                ['New Secret', $client->secret],
                ['Expires At', $client->expires_at?->format('Y-m-d H:i') ?? 'Never'],
                ['Rotated At', $client->last_rotated_at->format('Y-m-d H:i')],
            ]
        );

        $this->warn('⚠️  Update the client application with the new keys immediately.');

        return 0;
    }

    private function renewClient(): int
    {
        $client = $this->findClient();
        if (! $client) {
            return 1;
        }

        $days = (int) ($this->option('days') ?? 30);
        $client = ApiKeyService::renewExpiry($client, $days);

        $this->info("✅ Expiry renewed for \"{$client->name}\" — now expires at {$client->expires_at->format('Y-m-d H:i')}");

        return 0;
    }

    private function listClients(): int
    {
        $clients = ApiClient::all();

        if ($clients->isEmpty()) {
            $this->info('No API clients found.');

            return 0;
        }

        $rows = $clients->map(function (ApiClient $c) {
            $status = match (true) {
                ! $c->is_active => '🔴 Revoked',
                $c->isExpired() => '🟡 Expired',
                default => '🟢 Active',
            };
            $expiry = $c->expires_at?->format('Y-m-d H:i') ?? 'Never';
            $lastRotated = $c->last_rotated_at?->format('Y-m-d H:i') ?? '—';

            return [$c->id, $c->name, substr($c->api_key, 0, 12).'...', $expiry, $lastRotated, $status];
        })->toArray();

        $this->table(
            ['ID', 'Name', 'API Key (prefix)', 'Expires', 'Last Rotated', 'Status'],
            $rows
        );

        return 0;
    }

    private function revokeClient(): int
    {
        $client = $this->findClient();
        if (! $client) {
            return 1;
        }

        if (! $this->confirm("Revoke \"{$client->name}\"? All its requests will be rejected.")) {
            return 0;
        }

        $client->update(['is_active' => false]);
        $this->info("🔴 Client \"{$client->name}\" has been revoked.");

        return 0;
    }

    private function findClient(): ?ApiClient
    {
        $id = $this->argument('value');
        if (! $id) {
            $this->error('Client ID is required.');

            return null;
        }

        $client = ApiClient::find($id);
        if (! $client) {
            $this->error("Client with ID {$id} not found.");

            return null;
        }

        return $client;
    }
}
