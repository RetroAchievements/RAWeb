<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\ApiLogEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Sentry\Laravel\Integration as SentryIntegration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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

    protected $dontReport = [
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            SentryIntegration::captureUnhandledException($e);
        });
    }

    /**
     * Report or log an exception.
     *
     * @throws Throwable
     */
    public function report(Throwable $e): void
    {
        // Filter out React errors caused by Google Translate modifying the DOM during SSR.
        if ($e instanceof \Inertia\Ssr\SsrException
            && preg_match('/React does not recognize|Invalid attribute name/', $e->getMessage())
        ) {
            return;
        }

        parent::report($e);
    }

    protected function buildExceptionContext(Throwable $e): array
    {
        $context = parent::buildExceptionContext($e);

        $request = request();
        if ($request && !app()->runningInConsole()) {
            $context['url'] = $request->url();

            // never log raw passwords
            $params = Arr::except($request->all(), $this->dontFlash);

            // extract the user and token parameters for API calls
            if (str_ends_with($context['url'], 'dorequest.php')) {
                unset($params['u']);
                unset($params['t']);

                $method = $params['r'] ?? '';
                if ($method === 'login') {
                    unset($params['p']);
                }
            } elseif (str_contains($context['url'], '/API/')) {
                unset($params['z']);
                unset($params['y']);
            }

            // truncate long parameters
            foreach ($params as $k => $p) {
                if (is_string($p) && strlen($p) > 20) {
                    $params[$k] = substr($p, 0, 15) . "...";
                }
            }

            // capture any remaining parameters
            $context['params'] = http_build_query($params);
        } elseif ($request) {
            // running in console - dump command line parameters
            $argv = $request->server('argv', null);
            if ($argv) {
                $context['argv'] = json_encode($argv);
            }
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
        // TODO do not redirect in views, refactor to controller when needed
        if ($e instanceof ViewRedirect) {
            return $e->redirect;
        }

        if ($request->is('api/v2/*')) {
            return $this->renderWebApiV2Exception($e);
        }

        if ($request->expectsJson()) {
            if ($e instanceof JsonApiException) {
                $errors = $e->getErrors()->toArray();
                $message = isset($errors[0]['title']) ? $errors[0]['title'] : 'JSON:API error';

                return response()->json([
                    'message' => $message,
                    'errors' => $errors,
                ], $e->getStatusCode());
            }
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => __($e->getMessage() ?: Response::$statusTexts[$e->getStatusCode()] ?? ''),
                    'errors' => [
                        [
                            'status' => (string) $e->getStatusCode(),
                            'code' => Str::snake(Response::$statusTexts[$e->getStatusCode()] ?? $e->getStatusCode()),
                            'title' => __($e->getMessage() ?: Response::$statusTexts[$e->getStatusCode()] ?? ''),
                        ],
                    ],
                ], $e->getStatusCode());
            }
            if ($e instanceof TokenMismatchException) {
                return response()->json([
                    'message' => __('Token Mismatch'),
                    'errors' => [
                        [
                            'status' => '419',
                            'code' => 'token_mismatch',
                            'title' => __('Token Mismatch'),
                        ],
                    ],
                ], 419);
            }
            if ($e instanceof AuthenticationException) {
                $responseData = [
                    'message' => __($e->getMessage() ?: Response::$statusTexts[401]),
                    'errors' => [
                        [
                            'status' => '401',
                            'code' => Str::snake(Response::$statusTexts[401]),
                            'title' => __($e->getMessage() ?: Response::$statusTexts[401]),
                        ],
                    ],
                ];

                // Log failed auth attempts for the internal service API.
                if ($request->is('api/internal/*')) {
                    $responseSize = strlen(json_encode($responseData));

                    ApiLogEntry::logRequest(
                        'internal',
                        null,
                        $request->path(),
                        $request->method(),
                        401,
                        0,
                        $responseSize,
                        $request->ip(),
                        $request->userAgent(),
                        null,
                        'Unauthenticated'
                    );
                }

                return response()->json($responseData, 401);
            }
            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'message' => __($e->getMessage() ?: Response::$statusTexts[403]),
                    'errors' => [
                        [
                            'status' => '403',
                            'code' => Str::snake(Response::$statusTexts[403]),
                            'title' => __($e->getMessage() ?: Response::$statusTexts[403]),
                        ],
                    ],
                ], 403);
            }
            if ($request->is('dorequest.php')) {
                $message = $e->getMessage();

                // strip out any queries in the exception, the client won't know what to do with
                // them, and we don't want to expose anything sensitive that might be in the query.
                if ($e instanceof QueryException) {
                    // format should be "${previous->message} (Connection: $name, SQL: $sql)"
                    // https://github.com/laravel/framework/blob/b6f2ea681b9411a86c9a70c8bfb6ff890a457187/src/Illuminate/Database/QueryException.php#L67
                    // so capture the previous message if it's available
                    $previous = $e->getPrevious();
                    if ($previous) {
                        $message = $previous->getMessage();
                    }
                }
                // look for possible SQL queries coming from other exception types
                $index = strpos($message, 'SQL:');
                if ($index !== false) {
                    $message = trim(substr($message, 0, $index));
                }

                // if it's a resource error, return 503 Temporarily Unavailable, otherwise return
                // 500 Internal Server Error
                $statusCode = 500;
                if (str_contains($message, 'Too many connections')) {
                    $statusCode = 503;
                }

                // dorequest response expects these fields capitalized and at the top level
                return response()->json([
                    'Success' => false,
                    'Error' => $message,
                    'Status' => $statusCode,
                ], $statusCode);
            }
        }

        // Handle banned user exceptions with a contextual 404 page.
        if ($e instanceof BannedUserException && !$request->expectsJson()) {
            return response()->view('errors.404', ['isBannedUser' => true], 404);
        }

        return parent::render($request, $e);
    }

    private function renderWebApiV2Exception(Throwable $e): Response
    {
        if ($e instanceof JsonApiException) {
            $errors = $e->getErrors()->toArray();

            return response()->json([
                'message' => $errors[0]['title'] ?? 'JSON:API error',
                'errors' => $errors,
            ], $e->getStatusCode());
        }

        $status = match (true) {
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException => 403,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };

        $statusText = Response::$statusTexts[$status] ?? 'Error';
        $title = $e->getMessage() ?: $statusText;

        return response()->json([
            'message' => $title,
            'errors' => [
                [
                    'status' => (string) $status,
                    'code' => Str::snake($statusText),
                    'title' => $title,
                ],
            ],
        ], $status);
    }
}
