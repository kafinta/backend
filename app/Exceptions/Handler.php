<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    }

    /**
     * Render an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'status' => 'fail',
            'status_code' => 401,
            'message' => 'Unauthenticated. Please login to continue.'
        ], 401);
    }

    public function render($request, Throwable $exception)
    {
        // Handle route not found exception
        if ($exception instanceof \Symfony\Component\Routing\Exception\RouteNotFoundException) {
            return response()->json([
                'status' => 'fail',
                'status_code' => 401,
                'message' => 'Unauthenticated. Please login to continue.'
            ], 401);
        }

        // Handle model not found exception
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $modelName = strtolower(class_basename($exception->getModel()));
            return response()->json([
                'status' => 'fail',
                'status_code' => 404,
                'message' => "The requested {$modelName} was not found."
            ], 404);
        }

        // Handle non-existent routes
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'status' => 'fail',
                'status_code' => 404,
                'message' => 'The requested route does not exist.'
            ], 404);
        }

        return parent::render($request, $exception);
    }
}
