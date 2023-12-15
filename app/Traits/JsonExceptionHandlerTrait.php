<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Throwable;


trait JsonExceptionHandlerTrait
{
    /**
     * Returns JSON response for model not found exception.
     *
     * @param Throwable $exception
     *
     * @throws \ReflectionException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function modelNotFound(Throwable $exception)
    {
        /** @var ModelNotFoundException $exception */
        $model = new ReflectionClass($exception->getModel());

        return $this->respondWithError(
            $model->getShortName().' not found.',
            404
        );
    }

    /**
     * Returns JSON response for Eloquent model not found exception.
     *
     * @param Throwable $exception
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function httpClientError(Throwable $exception)
    {
        return $this->respondWithError(
            "{$exception->getMessage()}",
            $exception->getCode()
        );
    }

    /**
     * Returns JSON response for Eloquent model not found exception.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function routeNotFound()
    {
        return $this->respondWithError(
            'The requested URI is invalid.',
            404
        );
    }

    protected function validationError(Throwable $exception)
    {
        /* @var $exception ValidationException */
        return $this->respondWithError(
            $exception->validator->errors()->first(),
            422
        );
    }

    protected function authorizationError(Throwable $exception)
    {
        return $this->respondWithError(
            $exception->getMessage(),
            403
        );
    }

    protected function unauthenticatedError(Throwable $exception)
    {
        return $this->respondWithError(
            $exception->getMessage(),
            401
        );
    }

    /**
     * Returns JSON response for idempotent header exception.
     *
     * @param Throwable $exception
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function idempotentHeaderError(Throwable $exception)
    {
        return $this->respondWithError(
            "{$exception->getMessage()}",
            $exception->getCode()
        );
    }

    /**
     * Returns json response error.
     *
     * @param       $message
     * @param mixed $statusCode
     * @param mixed $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithError($message, $statusCode, $headers = [])
    {
        return response()->json([
            'status'      => 'fail',
            'status_code' => $statusCode,
            'message'       => $message
        ], $statusCode, $headers);
    }

    protected function respondWithValidationError($error, $statusCode)
    {
        return response()->json([
            'status'      => 'fail',
            'status_code' => $statusCode,
            'message'       => $error,
        ], $statusCode);
    }

    /**
     * Returns json response success.
     *
     * @param       $message
     * @param mixed $statusCode
     * @param mixed $headers
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithSuccess($message, $statusCode, $data = [], $headers = [])
    {
        return response()->json([
            'status'      => 'success',
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data,
        ], $statusCode, $headers);
    }


    /**
     * Returns json response for custom Exception message.
     *
     * @param       $message
     * @param mixed $statusCode
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function exceptionError($message, $statusCode)
    {

        if($statusCode === 500)
        {
            $filteredMessage = env('APP_DEBUG') == true ? $message : "Something went wrong, please try again later";
        }
        elseif($statusCode === 503)
        {
            $filteredMessage = env('APP_DEBUG') == true ? $message : "Service Unavailable, please try again later";
        }else {
            $filteredMessage = env('APP_DEBUG') == true ? $message : "Internal Error, please try again later";
        }

        return response()->json([
            'status'      => 'fail',
            'status_code' => $statusCode,
            'message'  => $filteredMessage,
        ], $statusCode);
    }

    /**
     * Returns json response for validation errors.
     *
     * @param       $message
     *
     * @return \Illuminate\Http\JsonResponse
     */

    protected function validatorError($message)
    {
        return response()->json([
            'status'      => 'fail',
            'status_code' => 422,
            'message'  => $message,
        ], 422);
    }



}