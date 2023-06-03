<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [

    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register()
    {
    }

    /**
     * Report or log an exception.
     *
     * @throws Throwable
     */
    public function report(Throwable $e)
    {
        parent::report($e);
    }

    protected function buildExceptionContext(Throwable $e)
    {
        $context = parent::buildExceptionContext($e);

        $request = request();
        if ($request) {
            $context['url'] = $request->url();

            // never log raw passwords
            $params = Arr::except($request->all(), $this->dontFlash);

            // extract the user and token parameters for API calls
            if (str_ends_with($context['url'], 'dorequest.php')) {
                unset($params['u']);
                unset($params['t']);
            }
            elseif (str_contains($context['url'], '/API/')) {
                unset($params['z']);
                unset($params['y']);
            }

            // truncate long parameters
            foreach ($params as $k => $p) {
                if (strlen($p) > 20)
                    $params[$k] = substr($p, 0, 15) . "...";
            }

            // capture any remaining parameters
            $context['params'] = http_build_query($params);
        }

        return $context;
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response
    {
        if ($request->expectsJson()) {
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => __($e->getMessage() ?: Response::$statusTexts[$e->getStatusCode()] ?? ''),
                    'errors' => [
                        [
                            'status' => $e->getStatusCode(),
                            'code' => Str::snake(Response::$statusTexts[$e->getStatusCode()] ?? $e->getStatusCode()),
                            'title' => __($e->getMessage() ?: Response::$statusTexts[$e->getStatusCode()] ?? ''),
                        ],
                    ],
                ], $e->getStatusCode());
            }
            if (is_a($e, TokenMismatchException::class)) {
                return response()->json([
                    'message' => __('Token Mismatch'),
                    'errors' => [
                        [
                            'status' => 419,
                            'code' => 'token_mismatch',
                            'title' => __('Token Mismatch'),
                        ],
                    ],
                ], 419);
            }
            if (is_a($e, AuthenticationException::class)) {
                return response()->json([
                    'message' => __($e->getMessage() ?: Response::$statusTexts[401]),
                    'errors' => [
                        [
                            'status' => 419,
                            'code' => Str::snake(Response::$statusTexts[401]),
                            'title' => __($e->getMessage() ?: Response::$statusTexts[401]),
                        ],
                    ],
                ], 401);
            }
            if (is_a($e, AuthorizationException::class)) {
                return response()->json([
                    'message' => __($e->getMessage() ?: Response::$statusTexts[403]),
                    'errors' => [
                        [
                            'status' => 403,
                            'code' => Str::snake(Response::$statusTexts[403]),
                            'title' => __($e->getMessage() ?: Response::$statusTexts[403]),
                        ],
                    ],
                ], 403);
            }
        }

        if (is_a($e, AuthenticationException::class)) {
            // parent::render will call parent::unauthenticated for AuthenticationException,
            // which redirects to route('login') unless the exception specifies another target.
            // Since we don't define a login route, this causes an exception. If no redirect
            // route is provided, just fail with 401 Not Authorized.
            if (empty($e->redirectTo())) {
                abort(401);
            }
        }

        return parent::render($request, $e);
    }
}
