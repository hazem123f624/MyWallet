<?php

namespace App\Classes\TestCases;

/**
 * Test Case Builder
 *
 * Provides a Fluent API to define API test cases for automatic documentation generation.
 * This class eliminates the need for complex nested arrays in configuration files.
 *
 * Usage Examples:
 *
 *   // 1. Simple GET request:
 *   TestCaseBuilder::make('List Admins')
 *       ->get('/api/v1/admins')
 *       ->expectStatus(200)
 *       ->expectKeys(['id', 'email'])
 *       ->inDocs('Admins List', 'Retrieve all admins from the system')
 *       ->build();
 *
 *   // 2. POST request with parameters and auth:
 *   TestCaseBuilder::make('Create Admin')
 *       ->post('/api/v1/admins')
 *       ->auth('your-token-here')
 *       ->params(['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'secret123'])
 *       ->expectStatus(201)
 *       ->inDocs('Store Admin')
 *       ->build();
 *
 *   // 3. Request with custom headers:
 *   TestCaseBuilder::make('API with Custom Header')
 *       ->get('/api/v1/resource')
 *       ->headers(['X-Custom-Header' => 'SpecialValue'])
 *       ->expectStatus(200)
 *       ->build();
 */
class TestCaseBuilder
{
    private string $name;
    private string $method = 'GET';
    private string $url = '';
    private bool|string $authToken = false;
    private array $headers = [];
    private array $notes = [];
    private array $params = [];
    private array $queryParams = [];
    private array $returnKeys = [];
    private array $appends = [];
    private int $successCode = 200;
    private array|bool $inDocs = false;
    private string $longDescription = '';
    private array $files = [];

    /**
     * Start building a new test case.
     */
    public static function make(string $name): self
    {
        $instance = new self();
        $instance->name = $name;
        return $instance;
    }

    public function method(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function get(string $url): self
    {
        $this->method = 'GET';
        $this->url = $url;
        return $this;
    }

    public function post(string $url): self
    {
        $this->method = 'POST';
        $this->url = $url;
        return $this;
    }

    public function put(string $url): self
    {
        $this->method = 'PUT';
        $this->url = $url;
        return $this;
    }
    public function patch(string $url): self
    {
        $this->method = 'PATCH';
        $this->url = $url;
        return $this;
    }

    public function delete(string $url): self
    {
        $this->method = 'DELETE';
        $this->url = $url;
        return $this;
    }

    public function params(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function query(array $params): self
    {
        $this->queryParams = $params;
        return $this;
    }

    public function headers(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }
    public function notes(array $notes): self
    {
        $this->notes = $notes;
        return $this;
    }


    public function auth(string|bool $token): self
    {
        $this->authToken = $token;
        return $this;
    }

    public function expectStatus(int $code): self
    {
        $this->successCode = $code;
        return $this;
    }

    public function expectKeys(array $keys): self
    {
        $this->returnKeys = $keys;
        return $this;
    }

    public function expectAppends(array $appends): self
    {
        $this->appends = $appends;
        return $this;
    }

    /**
     * Add files to be attached to the request
     *
     * @param array $files Array of file field names to attach
     * Example: ->files(['image', 'document'])
     */
    public function files(array $files): self
    {
        $this->files = $files;
        return $this;
    }

    /**
     * Include this test case in the generated documentation.
     *
     * @param string $shortDescription
     * @param string $description
     * @param int $order Sorting order in documentation
     */
    public function inDocs(string $shortDescription = '', string $description = '', int $order = 1): self
    {
        $this->inDocs = [
            'name' => $this->name,
            'short_description' => $shortDescription ?: $this->name,
            'description' => $description ?: $this->longDescription ?: $this->name,
            'notes' => $this->notes,
            'order' => $order,
        ];
        return $this;
    }

    public function description(string $description): self
    {
        $this->longDescription = $description;
        if (is_array($this->inDocs)) {
            $this->inDocs['description'] = $description;
        }
        return $this;
    }

    /**
     * Convert the builder instance to the array format expected by the documentation engine.
     */
    public function build(): array
    {
        return [
            $this->name => [
                'method' => $this->method,
                'url' => $this->url,
                'auth_token' => $this->authToken,
                'headers' => $this->headers,
                'notes' => $this->notes,
                'params' => $this->params,
                'query_params' => $this->queryParams,
                'return_keys' => $this->returnKeys,
                'appends' => $this->appends,
                'success_code' => $this->successCode,
                'in_docs' => $this->inDocs,
                'files' => $this->files,
            ]
        ];
    }
}
