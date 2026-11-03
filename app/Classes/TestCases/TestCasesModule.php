<?php

namespace App\Classes\TestCases;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Services\Documentations\Enums\ApiTestedStatus;
use App\Services\Documentations\Enums\DocumentationSections;
use App\Services\Documentations\Interfaces\DocumentationsInterface;

class TestCasesModule
{
    const GET = 'GET';

    const POST = 'POST';


    const PUT = 'PUT';

    const PATCH = 'PATCH';
    const DELETE = 'DELETE';

    public array $files = [];

    public string $module;

    public string $testCase;

    public int $testCasesResult = 0;

    public array $needToAddInDocumentation = [];

    public string $CaseKeyRunNow = '';

    public array $finalResult = [];

    private ?array $testCasesData = null;

    /**
     * @param string $module Module name
     * @param string $testCase The specific test case/type to run
     * @param array $files Files to attach to requests
     * @param array|null $testCasesData Optional pre-loaded test cases data
     */
    public function __construct($module, $testCase, $files = [], ?array $testCasesData = null)
    {
        $this->module = $module;
        $this->testCase = $testCase;
        $this->files = $files;
        $this->testCasesData = $testCasesData;
        $this->finalResult = [
            'status' => true,
            'message' => 'success',
        ];
    }

    /**
     * Run the test cases and process the results.
     */
    public function handle(): array
    {
        $modelNameSmallCase = str()->snake($this->module);

        // Use provided test cases data (must be provided via current redesign)
        $testCases = $this->testCasesData;
        if (!$testCases || !isset($testCases[$this->testCase])) {
            $this->error("Test case [{$this->testCase}] not found in DocumentationCases class.");
            return $this->finalResult;
        }
        foreach ($testCases[$this->testCase] as $key => $senarios) {
            $cases = isset($senarios['method']) ? [$senarios] : $senarios;
            foreach ($cases as $testCaseKey => $testCase) {
                // If we already have an error, stop or skip?
                // Currently continuing, but preventing success from overwriting error status.
                $keyName = str()->slug($modelNameSmallCase.' '.$this->testCase.' '.$testCaseKey);
                if (isset($testCase['in_docs']) && $testCase['in_docs']) {
                    $this->CaseKeyRunNow = $keyName;
                    $this->needToAddInDocumentation[$this->CaseKeyRunNow] = $testCase['in_docs'];
                }
                switch ($testCase['method']) {
                    case self::POST:
                        $this->PostRequest($testCase, $keyName);
                        break;
                    case self::PUT:
                        $this->PostRequest($testCase, $keyName, self::PUT);
                        break;
                    case self::PATCH:
                        $this->PostRequest($testCase, $keyName, self::PATCH);
                        break;
                    case self::DELETE:
                        $this->GetRequest($testCase, $keyName, self::DELETE);
                        break;
                    default:
                        $this->GetRequest($testCase, $keyName);
                }
            }
        }
//        dd(!empty($this->needToAddInDocumentation) , $this->testCasesResult ,count($testCases[$this->testCase]), $testCases[$this->testCase]);
        // Only save to documentation if ALL tests passed
        if (!empty($this->needToAddInDocumentation) && $this->testCasesResult == count($testCases[$this->testCase])) {
            // Note: The count logic depends on the array structure.
            $this->setInDocumentation();
        }

        return $this->finalResult;
    }

    private function PostRequest($case, $caseTitles, $method = self::POST): void
    {
        try {
            $request = $this->handelRequest($case['params']);
            $url = config('app.url').$case['url'];

            $withFiles = [];
            $filesToAttach = $this->files;
            if (isset($case['files']) && is_array($case['files'])) {
                $filesToAttach = array_merge($filesToAttach, $case['files']);
            }

            $hasFiles = !empty($filesToAttach);
            $headers = $this->handelHeaders($case, $hasFiles);
            $testCase = Http::withHeaders($headers);
            $notes = $this->handelNotes($case);
            // Add Query Parameters if exist
            $queryParams = [];
            if (isset($case['query_params']) && !empty($case['query_params'])) {
                $queryParams = $case['query_params'];
                $testCase = $testCase->withQueryParameters($queryParams);
            }

            foreach ($filesToAttach as $file) {
                $attach = $this->handelAttachedFiles($file);
                unset($request[$file]);
                $withFiles[] = $file;
                $testCase = $testCase->attach($attach[0], $attach[1], $attach[2]);
            }
            if ($hasFiles) {
                if ($method == self::PUT) {
                    $testCase = $testCase->post($url, array_merge(['_method' => 'PUT'], $request));
                } elseif ($method == self::PATCH) {
                    $testCase = $testCase->post($url, array_merge(['_method' => 'PATCH'], $request));
                } else {
                    $testCase = $testCase->post($url, $request);
                }
            } else {
                if ($method == self::PUT) {
                    $testCase = $testCase->asJson()->put($url, $request);
                } elseif ($method == self::PATCH) {
                    $testCase = $testCase->asJson()->patch($url, $request);
                } else {
                    $testCase = $testCase->asJson()->post($url, $request);
                }

            }
            if ($testCase->failed() && $testCase->status() != ($case['success_code'] ?? 0)) {
                dd(
                    'RESPONSE:', $testCase->json(),
                    'CASE:', $case,
                    'CASE TITLE:', $caseTitles,
                    'URL:', $url,
                    'METHOD:', $method,
                    'STATUS:', $testCase->status(),
                    'HEADERS SENT:', $headers,
                    'REQUEST:', $request,
                    'WITH FILES:', $withFiles,
                    'QUERY PARAMS:', $queryParams,
                );
            }
            // Safely extract and clean the response body
            $jsonBody = $testCase->json();
            if (is_array($jsonBody) || is_string($jsonBody)) {
                $body = $this->cleanUtf8($jsonBody);
            } elseif ($jsonBody === false || $jsonBody === null) {
                // Try to get raw body as string
                $rawBody = method_exists($testCase, 'body') ? $testCase->body() : (string) $testCase;
                // If still not valid UTF-8, fallback to base64
                if (!mb_check_encoding($rawBody, 'UTF-8')) {
                    $body = base64_encode($rawBody);
                } else {
                    $body = $this->cleanUtf8($rawBody);
                }
            } else {
                $body = '[Unserializable response body]';
            }
            if (!is_string($body) && !is_array($body)) {
                dd('BODY TYPE:', gettype($body), $body);
            }
            $testCaseResult = [
                'success_code' => $testCase->successful(),
                'method' => $method,
                'url' => $case['url'],
                'status' => $testCase->status(),
                'body' => $body,
                'request' =>[
                    'headers' => $headers,
                    'notes' => $notes,
                    'body' => $this->makeSerializable($request),
                    'files' => $withFiles,
                    'query_params' => $queryParams,
                ] ,
            ];
            $json = $this->safeJsonEncode($testCaseResult);
//            dd([
//                'json_result' => $json,
//                'json_error' => json_last_error_msg(),
//                'testCaseResult' => $testCaseResult
//            ]);
            $this->checkCase($case, $testCaseResult, $caseTitles);
        } catch (\Exception $exception) {
            dd($exception->getMessage(),$exception->getFile(),$exception->getLine());
            $this->error($exception->getMessage());

            return;
        }
    }

    private function GetRequest($case, $caseTitle, $method = self::GET): void
    {
        $url = config('app.url').$case['url'];
        $headers = $this->handelHeaders($case);
        $testCase = Http::withHeaders($headers);
        $notes = $this->handelNotes($case);
        $queryParams = [];
        // Add Query Parameters if exist
        if (isset($case['query_params']) && !empty($case['query_params'])) {
            $queryParams = $case['query_params'];
            $testCase = $testCase->withQueryParameters($queryParams);
        }

        if ($method == self::DELETE) {
            $testCase = $testCase->send('DELETE', $url, ['json' => $case['params']]);
        } else {
            $testCase = $testCase->get($url, $case['params']);
        }
        if ($testCase->failed() && $testCase->status() != ($case['success_code'] ?? 0)) {
            dd(
                'HEADERS SENT:', $headers,
                'RESPONSE:', $testCase->json(),
                'STATUS:', $testCase->status(),
                'URL:', $url,
                'METHOD:', $method,
                'QUERY PARAMS:', $queryParams,
                'CASE:', $case,
                'CASE TITLE:', $caseTitle,
            );
        }
        $testCaseResult = [
            'success_code' => $testCase->successful(),
            'method' => $method,
            'url' => $case['url'],
            'status' => $testCase->status(),
            'body' => $this->cleanUtf8($testCase->json()),
            'request' =>[
                'headers' => $headers,
                'notes' => $notes,
                'body' => [],
                'files' => [],
                'query_params' => $queryParams,
            ] ,
        ];
        $this->checkCase($case, $testCaseResult, $caseTitle);
    }

    private function handelHeaders($case, bool $hasFiles = false): array
    {
        $headers = $case['headers'] ?? [];
        $headers['Accept'] = 'application/json';
        if ($hasFiles) {
            unset($headers['Content-Type']);
        }
        if (isset($case['auth_token']) && $case['auth_token']) {
            $headers['Authorization'] = 'Bearer '.$case['auth_token'];
        }
        $this->needToAddInDocumentation[$this->CaseKeyRunNow]['documentation_body']['request']['headers'] = $headers;
        return $headers;
    }
    private function handelNotes($case): array
    {
        $notes = $case['notes'] ?? [];
        $this->needToAddInDocumentation[$this->CaseKeyRunNow]['documentation_body']['request']['notes'] = $notes;
        return $notes;
    }

    private function handelAttachedFiles($fileName): array
    {
        if ($this->isImageField($fileName)) {
            $file = UploadedFile::fake()->image('image.jpg', 100, 100);

            return [
                $fileName,
                $file,
                'image.jpg',
            ];
        }

        return [
            $fileName,
            self::MINIMAL_PDF,
            'document.pdf',
        ];
    }

    private function isImageField(string $field): bool
    {
        $imagePatterns = ['image', 'photo', 'picture', 'avatar', 'cover', 'logo', 'thumbnail'];

        foreach ($imagePatterns as $pattern) {
            if (str_contains($field, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private const MINIMAL_PDF = "%PDF-1.0\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 3 3]/Parent 2 0 R/Contents 4 0 R>>endobj\n4 0 obj<</Length 44>>stream\nBT /F1 1 Tf 0 0 Td (Hello World) Tj ET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000266 00000 n \ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n360\n%%EOF\n";

    private function handelRequest($params): array
    {
        return $params;
    }

    private function checkCase($case, $result, $caseTitle): void
    {
        $statusCode = $result['status'] == $case['success_code'];
        $body = true;

        if (!empty($case['return_keys'])) {
            if ($result['body'] !== null && $this->keysExist(['data'], $result['body'])) {
                if (isset($result['body']['pagination']) && !empty($result['body']['data'])) {
                    $body = $this->keysExist($case['return_keys'], $result['body']['data'][0]);
                } else {
                    $body = $this->keysExist($case['return_keys'], $result['body']['data']);
                }
            } else {
                $body = false;
            }
        }

        $appends = true;
        if (!empty($case['appends'])) {
            $appends = $this->keysExist($case['appends'], $result['body'] ?? []);
        }

        if (! $statusCode || ! $body || ! $appends) {
            $this->error($caseTitle.' case failed');
            return;
        } else {
            $this->testCasesResult++;
            $this->needToAddInDocumentation[$this->CaseKeyRunNow]['documentation_body'] = $result;
            $this->info($caseTitle.' case success');
        }
    }

    private function keysExist($keys, $data)
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Safely encode data to JSON, handling non-serializable objects
     *
     * @param mixed $data Data to encode
     * @param int $options JSON encode options
     * @return string JSON string (never fails, always returns valid JSON)
     */
    private function safeJsonEncode($data, int $options = JSON_UNESCAPED_UNICODE): string
    {
        try {
            $data = $this->makeSerializable($data);
            // Clean UTF-8 encoding issues
            $data = $this->cleanUtf8($data);

            // Use JSON_PARTIAL_OUTPUT_ON_ERROR to handle edge cases
            $encoded = json_encode($data, $options | JSON_PARTIAL_OUTPUT_ON_ERROR);

            if ($encoded === false) {
                return json_encode(['error' => 'Failed to serialize data']);
            }

            return $encoded;
        } catch (\Exception $e) {
            \Log::warning('JSON Encode Error: ' . $e->getMessage());
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Recursively convert non-serializable objects to arrays/strings
     *
     * @param mixed $data Data to make serializable
     * @param int $depth Recursion depth to prevent infinite loops
     * @return mixed Serializable data
     */
    private function makeSerializable($data, int $depth = 0)
    {
        // Prevent infinite recursion
        if ($depth > 10) {
            return '[MaxDepthReached]';
        }

        if (is_array($data)) {
            return array_map(fn($item) => $this->makeSerializable($item, $depth + 1), $data);
        }

        // Clean UTF-8 for strings FIRST before any processing
        if (is_string($data)) {
            return $this->cleanUtf8String($data);
        }

        if (is_object($data)) {
            // Handle UploadedFile - just get the filename
            if ($data instanceof \Illuminate\Http\UploadedFile) {
                return '[UploadedFile: ' . $this->cleanUtf8String($data->getClientOriginalName()) . ']';
            }

            // Handle Response objects from Guzzle/Http
            if ($data instanceof \Psr\Http\Message\ResponseInterface ||
                method_exists($data, 'getStatusCode')) {
                return '[Response: ' . ($data->getStatusCode() ?? 'unknown') . ']';
            }

            // Handle Stream objects
            if ($data instanceof \Psr\Http\Message\StreamInterface ||
                method_exists($data, 'getContents')) {
                return '[Stream]';
            }

            // Handle JsonSerializable objects
            if ($data instanceof \JsonSerializable) {
                try {
                    return $this->makeSerializable($data->jsonSerialize(), $depth + 1);
                } catch (\Exception $e) {
                    return '[JsonSerializable: ' . get_class($data) . ']';
                }
            }

            // Handle objects with __toString
            if (method_exists($data, '__toString')) {
                try {
                    return $this->cleanUtf8String((string) $data);
                } catch (\Exception $e) {
                    return '[Object: ' . get_class($data) . ']';
                }
            }

            // Convert object to array and recurse
            try {
                $objectAsArray = (array) $data;
                return $this->makeSerializable($objectAsArray, $depth + 1);
            } catch (\Exception $e) {
                return '[Object: ' . get_class($data) . ']';
            }
        }

        // Handle resources and closures
        if (is_resource($data)) {
            return '[Resource: ' . get_resource_type($data) . ']';
        }

        if ($data instanceof \Closure) {
            return '[Closure]';
        }

        // For numeric and boolean values, return as-is
        return $data;
    }

    /**
     * Clean a single string from UTF-8 encoding issues
     *
     * @param string $str String to clean
     * @return string Cleaned string
     */
    private function cleanUtf8String(string $str): string
    {
        // Try to fix malformed UTF-8
        if (!mb_check_encoding($str, 'UTF-8')) {
            // Try UTF-8 conversion first
            $cleaned = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            // If that fails, use iconv with IGNORE flag
            if ($cleaned === false || !mb_check_encoding($cleaned, 'UTF-8')) {
                $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $str);
            }
            return $cleaned ?: '';
        }
        return $str;
    }

    /**
     * Clean data from UTF-8 encoding issues
     * Removes or replaces invalid UTF-8 characters
     *
     * @param mixed $data Data to clean
     * @return mixed Cleaned data
     */
    private function cleanUtf8($data)
    {
        if (is_string($data)) {
            // Try to fix malformed UTF-8
            $cleaned = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // If conversion fails, use iconv to be more aggressive
            if ($cleaned === false || !mb_check_encoding($cleaned, 'UTF-8')) {
                $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $data);
            }
            return $cleaned ?: '';
        }

        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8'], $data);
        }

        return $data;
    }

    private function setInDocumentation(): void
    {

        $repository = app(DocumentationsInterface::class);
        foreach ($this->needToAddInDocumentation as $key => $value) {
            $notes = !empty($value['documentation_body']['request']['notes']) ? $this->safeJsonEncode($value['documentation_body']['request']['notes']) : null;
            $row = [
                'name' => $value['name'],
                'section' => DocumentationSections::API_DOCUMENTATIONS,
                'sorting' => $value['order'],
                'category' => $key,
                'short_description' => $value['short_description'],
                'description' => $value['description'],
                'developer_tested' => ApiTestedStatus::YES,
                'tester_tested' => ApiTestedStatus::NO,
                'headers' => $this->safeJsonEncode($value['documentation_body']['request']['headers']),
                'notes' => $notes,
                'route' => $value['documentation_body']['url'],
                'method' => $value['documentation_body']['method'],
            ];
            if (!empty($value['documentation_body']['request']['body'])) {
                $formBody = $value['documentation_body']['request']['body'];
                if (!empty($value['documentation_body']['request']['files'])) {
                    foreach ($value['documentation_body']['request']['files'] as $file) {
                        $formBody[$file] = 'input_file';
                    }
                }
                $row['body'] = $this->safeJsonEncode($formBody);
            }
            if (!empty($value['documentation_body']['request']['query_params'])) {
                $row['query_params'] = $this->safeJsonEncode($value['documentation_body']['request']['query_params']);
            }
            if ($value['documentation_body']['success_code']) {
                $row['success_response'] = $this->safeJsonEncode($value['documentation_body']['body']);
            } else {
                $row['error_response'] = $this->safeJsonEncode($value['documentation_body']['body']);
            }
            $repository->save($row);
        }
    }

    private function error($message): void
    {
        $this->finalResult['status'] = false;
        $this->finalResult['message'] = $message;
    }

    private function info($message): void
    {
        // Don't overwrite an existing error status
        if ($this->finalResult['status'] !== false) {
            $this->finalResult['status'] = true;
            $this->finalResult['message'] = $message;
        }
    }
}
