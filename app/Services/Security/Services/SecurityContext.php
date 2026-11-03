<?php

namespace App\Services\Security\Services;

/**
 * SecurityContext — Request-scoped value object.
 *
 * Collects security metadata as the request passes through middleware.
 * Stored on $request->attributes as 'security_context'.
 *
 * Each middleware that runs can enrich this context with its own data,
 * and the final enriched log entry is written once at the end.
 */
class SecurityContext
{
    public ?string $ip = null;
    public ?string $userAgent = null;
    public ?string $fingerprint = null;
    public ?int $apiClientId = null;
    public ?string $apiClientName = null;
    public ?int $riskScore = null;
    public ?string $nonce = null;
    public ?string $requestMethod = null;
    public ?string $requestPath = null;
    public array $flags = [];

    /**
     * Create a SecurityContext from the current request.
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        $ctx = new self();
        $ctx->ip = $request->ip();
        $ctx->userAgent = $request->userAgent();
        $ctx->requestMethod = $request->method();
        $ctx->requestPath = $request->path();

        // Generate fingerprint from IP + UA + Accept-Language
        $ctx->fingerprint = hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language', ''),
        ]));

        // If ApiKeyMiddleware already resolved a client, capture it
        $client = $request->attributes->get('api_client');
        if ($client) {
            $ctx->apiClientId = $client->id;
            $ctx->apiClientName = $client->name;
        }

        return $ctx;
    }

    /**
     * Add a flag to the context.
     */
    public function addFlag(string $key, mixed $value): self
    {
        $this->flags[$key] = $value;
        return $this;
    }

    /**
     * Convert context to array suitable for SecurityLog columns.
     */
    public function toLogAttributes(): array
    {
        return array_filter([
            'ip' => $this->ip,
            'risk_score' => $this->riskScore,
            'fingerprint' => $this->fingerprint,
            'nonce' => $this->nonce,
            'flags' => ! empty($this->flags) ? $this->flags : null,
            'user_agent' => $this->userAgent,
            'request_method' => $this->requestMethod,
            'request_path' => $this->requestPath,
            'api_client_id' => $this->apiClientId,
        ], fn ($v) => $v !== null);
    }
}
