<?php

namespace App\Exceptions;

use App\Repository\Helper\Response;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Exception $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof AuthorizationException)
            $response = response(Response::body(GeneralException::M_ACCESS_DENIED, GeneralException::ACCESS_DENIED), 403);
        else if ($exception instanceof IOTException) { # IOT exceptions
            $response = $this->CustomException($exception);
        } else { # other exceptions
            $response = $this->otherExceptions($request, $exception);
        }

        return $response;
    }

    /**
     * @param Exception $e
     * @return array
     */
    private function CustomException(Exception $e)
    {
        if ($e->getCode() == 701)
            $code = 401;
        else
            $code = 200;
        return response(Response::body($e->getMessage(), $e->getCode()), $code);
    }

    /**
     * @param $request
     * @param Exception $exception
     * @return JsonResponse|\Illuminate\Http\Response
     */
    private function otherExceptions($request, Exception $exception)
    {
        if (env('APP_DEBUG') == 'true') { # debug true
            $response = parent::render($request, $exception);
        } else { # debug false
            $response = response()->json(Response::body($exception->getMessage(), 500));
        }

        return $response;
    }
}
