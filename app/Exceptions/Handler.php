<?php

namespace App\Exceptions;

use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    use HttpResponsesTrait;

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        //        dd($exception->getStatusCode());

        // If request expects JSON (like from an API)
        if ($request->expectsJson()) {
            $exceptionAfterConvertToArray = json_decode(json_encode($exception), true);
            // Handle Not Found (401)
            if ($this->isHttpException($exception) && $exception->getStatusCode() === 401) {
                return $this->failure(message: __('messages.unauthorized'), status: 401);
            }
            // Handle Not Found (403)
            if ($this->isHttpException($exception) && $exception->getStatusCode() === 403) {
                return $this->failure(message: $exception->getMessage() ?: __('messages.you_are_not_allowed_to_perform_this_action'), status: 403);
            }

            // Handle Not Found (404)
            if ($this->isHttpException($exception) && $exception->getStatusCode() === 404) {
                return $this->failure(message: __('messages.resource_not_found'), status: 404);
            }
            // Handle Method Not Allowed (405)
            if ($this->isHttpException($exception) && $exception->getStatusCode() === 405) {
                return $this->failure(message: __('messages.method_not_allowed'), status: 405);
            }
            // Handle Not Found (419)
            if ($this->isHttpException($exception) && $exception->getStatusCode() === 419) {
                return $this->failure(message: __('messages.page_expired'), status: 419);
            }
            //  Handle Method Not Allowed (422)
            if (array_key_exists('status', $exceptionAfterConvertToArray) && $exceptionAfterConvertToArray['status'] === 422) {
                return $this->errors(message: $exception->getMessage(), data: $exception->errors(), status: 422, project: 'api');
            }

            // Handle ModelNotFoundException specifically (record not found)
            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->failure(message: __('messages.resource_not_found'), status: 404);
            }

            // Handle any other exception
            return response()->json([
                'success' => false,
                'message' => __('messages.server_error'),
            ], method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500);
        }

        // Default Laravel HTML error page
        return parent::render($request, $exception);
    }
}
