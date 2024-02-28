<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

use Illuminate\Database\QueryException;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            return $this->invalidJson($request, $exception);
        }

        if ($exception instanceof QueryException) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '500',
                        'title' => 'Database Error',
                        'detail' => 'Error procesando la respuesta. Inténtelo más tarde.'
                    ]
                ]
            ], 500);
        }
        // Si no salta el if
        return parent::render($request, $exception);
    }
    protected function invalidJson($request, ValidationException  $exception):JsonResponse
    {
        return response()->json([
            'errors' => collect($exception->errors())->map(function ($message, $field) use
            ($exception) {
                return [
                    'status' => '422',
                    'title' => 'Validation Error',
                    'details' => $message[0],
                    'source' => [
                        'pointer' => '/data/attributes/' . $field
                    ]
                ];
            })->values()
        ], $exception->status);
    }

}
