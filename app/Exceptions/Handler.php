<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

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
        $this->reportable(function (\Illuminate\Database\QueryException $exception) {
            \App\Exceptions\MedicalEvents\ClinicalStorageException::fromQueryException($exception, 'unhandledQuery');

            return false;
        });
        $this->renderable(function (\Illuminate\Database\QueryException|\App\Exceptions\MedicalEvents\ClinicalStorageException $exception, $request) {
            $message = $exception instanceof \App\Exceptions\MedicalEvents\ClinicalStorageException
                ? $exception->getMessage() : __('clinical-storage.write_failed');

            return $request->expectsJson()
                ? response()->json(['message' => $message], 503)
                : response($message, 503)->header('Content-Type', 'text/plain; charset=UTF-8');
        });
    }
}
