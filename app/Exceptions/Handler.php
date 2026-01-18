<?php

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
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Illuminate\Database\QueryException $e, $request) {
            // SQLSTATE[42S02]: Base table or view not found
            // SQLSTATE[42S22]: Column not found
            if ($e->getCode() === '42S02' || $e->getCode() === '42S22') {
                if (!$request->is('system/*')) { // Prevent infinite loops if the error page itself has DB issues
                    return response()->view('errors.db-update-required', [], 500);
                }
            }
        });
    }
}
