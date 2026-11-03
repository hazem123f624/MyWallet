<?php

use Illuminate\Support\Facades\Log;

if (! function_exists('logException')) {
    function logException(\Throwable $exception, $message = null)
    {
        $message = $message ?? $exception->getMessage();
        Log::error($message, [
            'message' => $exception->getMessage(),
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}

if (! function_exists('paginationData')) {
    /**
     * Build the data response array containing paginated items and pagination meta.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Contracts\Pagination\Paginator  $paginator
     * @param  mixed  $items
     * @return array
     */
    function paginationData($paginator, $items = null): array
    {
        return [
            'data' => $items ?? $paginator->items(),
            'pagination' => [
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total' => method_exists($paginator, 'total') ? $paginator->total() : count($paginator->items()),
                'last_page' => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
            ],
        ];
    }
}
