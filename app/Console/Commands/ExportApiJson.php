<?php

namespace App\Console\Commands;

use App\Models\Documentations;
use App\Services\Documentations\DocumentationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExportApiJson extends Command
{
    protected $signature = 'app:export-api-json {--path=api.json : Path relative to the project root}';

    protected $description = 'Export the documented API endpoints to a Postman collection JSON file.';

    public function handle(DocumentationService $documentationService): int
    {
        $documentations = Documentations::query()
            ->where('section', 1)
            ->where('is_active', 1)
            ->whereNotNull('route')
            ->whereNotNull('method')
            ->orderBy('sorting')
            ->orderBy('id')
            ->get();

        $sections = [];
        foreach ($documentations as $documentation) {
            [$section, $module] = $documentationService->classify($documentation->route, $documentation->method);
            $sections[$section][$module][] = $this->requestItem($documentation);
        }

        $collection = [
            'info' => [
                '_postman_id' => (string) Str::uuid(),
                'name' => config('app.name').' API',
                'description' => 'Generated from the MyWallet API documentation.',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    ['key' => 'token', 'value' => '{{token}}', 'type' => 'string'],
                ],
            ],
            'item' => $this->folders($sections),
            'variable' => [
                ['key' => 'base_url', 'value' => rtrim((string) config('app.url'), '/')],
                ['key' => 'api_prefix', 'value' => (string) config('api.version', 'v1')],
                ['key' => 'token', 'value' => ''],
                ['key' => 'api_key', 'value' => ''],
            ],
        ];

        $path = (string) $this->option('path');
        $fullPath = str_starts_with($path, '/') ? $path : base_path($path);

        File::ensureDirectoryExists(dirname($fullPath));
        File::put(
            $fullPath,
            json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
        );

        $this->info("API JSON exported to {$fullPath}");

        return self::SUCCESS;
    }

    private function folders(array $sections): array
    {
        $ordered = [];
        foreach (array_keys(config('documentation.consumers', [])) as $section) {
            if (isset($sections[$section])) {
                $ordered[$section] = $sections[$section];
            }
        }
        foreach ($sections as $section => $modules) {
            if (! isset($ordered[$section])) {
                $ordered[$section] = $modules;
            }
        }

        $folders = [];
        foreach ($ordered as $section => $modules) {
            $items = [];
            foreach ($modules as $module => $requests) {
                $items[] = ['name' => $module, 'item' => $requests];
            }
            $folders[] = ['name' => $section, 'item' => $items];
        }

        return $folders;
    }

    private function requestItem(Documentations $documentation): array
    {
        $body = $this->decode($documentation->body);
        $query = $this->decode($documentation->query_params);
        $notes = $this->decode($documentation->notes);

        $request = [
            'method' => strtoupper((string) $documentation->method),
            'header' => $this->headers($documentation->headers),
            'url' => $this->url((string) $documentation->route, $query),
            'description' => $this->description($documentation, $notes),
        ];

        if (! empty($body)) {
            $request['body'] = [
                'mode' => 'raw',
                'raw' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'options' => ['raw' => ['language' => 'json']],
            ];
        }

        $item = [
            'name' => $documentation->name,
            'request' => $request,
        ];

        $responses = $this->responses($documentation, $request);
        if ($responses !== []) {
            $item['response'] = $responses;
        }

        return $item;
    }

    private function headers(?string $headers): array
    {
        $defaults = [
            ['key' => 'Accept', 'value' => 'application/json'],
            ['key' => 'Content-Type', 'value' => 'application/json'],
        ];

        $decoded = $this->decode($headers);
        if (! is_array($decoded)) {
            return $defaults;
        }

        $result = [];
        foreach ($decoded as $key => $value) {
            if (is_array($value) && isset($value['key'])) {
                $result[] = $this->headerItem((string) $value['key'], (string) ($value['value'] ?? ''));
            } elseif (is_string($key)) {
                $result[] = $this->headerItem($key, (string) $value);
            }
        }

        return $result !== [] ? $result : $defaults;
    }

    private function headerItem(string $key, string $value): array
    {
        $normalized = strtolower($key);

        if ($normalized === 'x-api-token') {
            $value = '{{api_key}}';
        } elseif ($normalized === 'authorization') {
            $value = 'Bearer {{token}}';
        }

        return ['key' => $key, 'value' => $value];
    }

    private function url(string $route, mixed $query): array
    {
        $path = parse_url($route, PHP_URL_PATH) ?: $route;
        $segments = array_values(array_filter(explode('/', $path), static fn ($segment) => $segment !== ''));
        $versionIndex = null;
        foreach ($segments as $index => $segment) {
            if ($index > 0 && $segments[$index - 1] === 'api' && preg_match('/^v\d+$/', $segment) === 1) {
                $versionIndex = $index;
                break;
            }
        }
        if ($versionIndex === null) {
            $versionIndex = array_search((string) config('api.version', 'v1'), $segments, true);
        }
        if ($versionIndex !== false && $versionIndex !== null) {
            $segments[$versionIndex] = '{{api_prefix}}';
        }

        $queryItems = [];
        $routeQuery = parse_url($route, PHP_URL_QUERY);
        if (is_string($routeQuery) && $routeQuery !== '') {
            parse_str($routeQuery, $parsed);
            foreach ($parsed as $key => $value) {
                $queryItems[] = ['key' => (string) $key, 'value' => is_array($value) ? implode(',', $value) : (string) $value];
            }
        }

        if (is_array($query)) {
            foreach ($query as $key => $value) {
                if (is_array($value) && isset($value['key'])) {
                    $queryItems[] = ['key' => (string) $value['key'], 'value' => (string) ($value['value'] ?? '')];
                } elseif (is_string($key)) {
                    $queryItems[] = ['key' => $key, 'value' => (string) $value];
                }
            }
        }

        $raw = '{{base_url}}/'.implode('/', $segments);
        $url = [
            'raw' => $raw,
            'host' => ['{{base_url}}'],
            'path' => $segments,
        ];

        if ($queryItems !== []) {
            $url['query'] = $queryItems;
            $url['raw'] .= '?'.http_build_query(array_column($queryItems, 'value', 'key'));
        }

        return $url;
    }

    private function description(Documentations $documentation, mixed $notes): string
    {
        $parts = array_values(array_filter([
            $documentation->short_description,
            $documentation->description,
        ]));

        if (is_array($notes) && $notes !== []) {
            $lines = ['**Validation**', '', '| Field | Rules |', '| --- | --- |'];
            foreach ($notes as $note) {
                if (isset($note['key'], $note['value'])) {
                    $lines[] = '| `'.$note['key'].'` | '.$note['value'].' |';
                }
            }
            $parts[] = implode("\n", $lines);
        }

        return implode("\n\n", $parts);
    }

    private function responses(Documentations $documentation, array $request): array
    {
        $responses = [];

        foreach (['Success' => $documentation->success_response, 'Error' => $documentation->error_response] as $name => $payload) {
            $decoded = $this->decode($payload);
            if ($decoded === null) {
                continue;
            }

            $responses[] = [
                'name' => $name,
                'originalRequest' => $request,
                'status' => $name === 'Success' ? 'OK' : 'Error',
                'code' => $name === 'Success' ? 200 : 422,
                '_postman_previewlanguage' => 'json',
                'header' => [['key' => 'Content-Type', 'value' => 'application/json']],
                'body' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
        }

        return $responses;
    }

    private function decode(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        return $value;
    }
}
