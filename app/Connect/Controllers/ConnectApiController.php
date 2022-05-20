<?php

declare(strict_types=1);

namespace App\Connect\Controllers;

use App\Connect\Concerns\AchievementRequests;
use App\Connect\Concerns\AuthRequests;
use App\Connect\Concerns\BootstrapRequests;
use App\Connect\Concerns\DevelopmentRequests;
use App\Connect\Concerns\GameRequests;
use App\Connect\Concerns\HeartbeatRequests;
use App\Connect\Concerns\LeaderboardRequests;
use App\Connect\Concerns\LegacyCompatProxyRequests;
use App\Connect\Concerns\TicketRequests;
use App\Http\Controller;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Connect RPC endpoint(s) that the integrations (RAIntegration, RetroArch) rely on
 * RA_Integration dorequest success/fail checks:
 *
 * @see https://github.com/RetroAchievements/RAIntegration/blob/master/src/api/impl/ConnectedServer.cpp#L39-L118
 * 1) HTTP status code = 200
 * 2) body is not empty
 * 3) JSON parses correctly
 * 4) if "Error" member present in JSON, response is error
 * 5) if "Success" is present in JSON and value is false, response is failure
 */
class ConnectApiController extends Controller
{
    use AuthRequests;
    use BootstrapRequests;
    use DevelopmentRequests;
    use HeartbeatRequests;
    use AchievementRequests;
    use GameRequests;
    use LeaderboardRequests;
    use TicketRequests;
    use LegacyCompatProxyRequests;

    /**
     * Format versioning
     * See https://cloud.google.com/blog/products/gcp/api-design-which-version-of-versioning-is-right-for-you
     */
    public const DEFAULT_VERSION = 2;

    public const TOKEN_EXPIRY_DAYS = 14;

    protected ?Authenticatable $user = null;
    protected int $acceptVersion;
    protected ?string $userAgent = null;

    /**
     * guest:
     *
     * allprogress TODO: deprecate?
     * hashlibrary TODO: deprecate?
     * officialgameslist TODO: deprecate?
     *
     * verified:
     *
     * richpresencepatch TODO: deprecate?
     * getfriendlist TODO: deprecate?
     */
    public function __construct()
    {
        /*
         * This controller should use a specific guard as default
         * Setting the auth: middleware on the route group would require the user to be
         * authenticated for all requests
         *
         * @see https://github.com/laravel/framework/issues/13788#issuecomment-249550532
         * TODO: implement guard switch https://stackoverflow.com/a/49074084
         */
        Auth::shouldUse('connect-token');
    }

    /**
     * @return Collection<int|string, string>
     */
    private function mask(mixed $input): Collection
    {
        return (new Collection($input))->mapWithKeys(function ($item, $key) {
            if (in_array($key, [
                'p',
                't',
                'Token',
            ])) {
                return [$key => '***'];
            }

            return [$key => $item];
        });
    }

    private function log(string $message, array $context = []): void
    {
        if (config('app.debug')) {
            Log::debug($message, $context);
        }
    }

    private function logRequest(array $context): void
    {
        if (config('app.debug')) {
            Log::debug('Request', $context);
        }
    }

    private function logResponse(array $context): void
    {
        if (config('app.debug')) {
            Log::debug('Response', $context);
        }
    }

    public function noop(Request $request, ?string $method = null): Response
    {
        $this->acceptVersion ??= self::DEFAULT_VERSION;

        return $this->errorResponse(405, 'Method not allowed');
    }

    public function request(Request $request): Response
    {
        try {
            $request->validate([
                'r' => 'required_without:method|string',
                'method' => 'required_without:r|string',
            ]);
        } catch (ValidationException $exception) {
            return $this->respond([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => $exception->errors(),
            ], 400);
        }

        $this->acceptVersion = (int) $request->header('Accept-Version', (string) ($this->acceptVersion ?? self::DEFAULT_VERSION));

        $userAgent = $request->header('User-Agent');
        $userAgent = is_array($userAgent) ? $userAgent[0] : $userAgent;
        $this->userAgent = $userAgent;

        if (!$this->validateUserAgent($this->userAgent)) {
            return $this->errorResponse(400);
        }

        $method = $request->get('method') ?? $request->get('r');

        /*
         * some methods may be defined but not implemented
         */
        if (!method_exists($this, mb_strtolower($method) . 'Method')) {
            return $this->errorResponse(501, null, 'Method "' . $method . '" does not exist');
        }

        /*
         * execute method, catch aborted requests
         * handle exceptions individually instead of the global exception handler
         * to add additional status flags (Success, etc)
         */
        try {
            return $this->respond($this->{mb_strtolower($method) . 'Method'}($request));
        } catch (ValidationException $exception) {
            return $this->errorResponse(400, $exception->getMessage(), $exception->errors());
        } catch (AuthenticationException $exception) {
            return $this->errorResponse(401, $exception->getMessage(), null);
        } catch (AuthorizationException $exception) {
            return $this->errorResponse(403, $exception->getMessage(), null);
        } catch (HttpException $exception) {
            return $this->errorResponse($exception->getStatusCode(), $exception->getMessage(), null);
        } catch (Exception $exception) {
            $error = null;
            if (config('app.debug')) {
                $error = [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    // 'trace' => $exception->getTrace(),
                ];
            }
            /*
             * Everything else is a server error with status 500
             * We don't want any database error codes forwarded as http status here
             */
            return $this->errorResponse(500, null, $error);
        }
    }

    private function isRetroArch(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        $validBrowsers = [
            'libretro',
            'retroarch',
        ];

        foreach ($validBrowsers as $validBrowser) {
            if (mb_strpos(mb_strtolower($userAgent), $validBrowser) === 0) {
                return true;
            }
        }

        return false;
    }

    private function validateUserAgent(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        $validBrowsers = [
            'libretro',
            'retroarch',
            'retroachievements client bootstrap',
            'retro achievements client',
            'ralibretro',
            'rasnes9x',
            'ranes',
            'ravisualboyadvance',
            'rap64',
            'rapplewin',
            'ragens',
            'rameka',
            'raquasi88',
        ];

        foreach ($validBrowsers as $validBrowser) {
            if (mb_strpos(mb_strtolower($userAgent), $validBrowser) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|array|Collection<int|string, mixed>|null $data
     */
    private function respond(string|array|Collection|null $data = null, int $status = 200): Response
    {
        if (is_string($data)) {
            return response($data, $status);
        }

        $data = Arr::wrap($data);

        if (!isset($data['success'])) {
            $data['success'] = $status >= 200 && $status < 400;
        }

        $this->logRequest($this->mask(request()->input())->toArray());
        $this->logResponse($this->mask($data)->toArray());

        if ($this->acceptVersion == 1) {
            /**
             * TODO: instead of some formatting make that properly versioned resources
             */
            $data = $this->version1Formatted($data);
        }

        return response()->json($data, $status, [
            'Content-Version' => 1,
        ]);
    }

    private function errorResponse(int $status, ?string $message = null, mixed $error = null): Response
    {
        $code = $status;

        $errorMessage = Response::$statusTexts[$status] ?? null;

        if ($errorMessage) {
            $code = Str::snake($errorMessage);
        }

        if ($message) {
            $errorMessage = $message;
        }

        $data = [
            'success' => false,
            'code' => $code,
        ];

        if (empty($error)) {
            $error = $errorMessage;
        }
        if ($status === 401) {
            $error = 'Invalid credentials. Please log in again.';
        }

        $data[is_array($error) ? 'errors' : 'error'] = $error;

        return $this->respond($data, $this->isRetroArch($this->userAgent) ? 200 : $status);
    }

    /**
     * Version 1 format: inconsistent PascalCase
     */
    private function version1Formatted(mixed $value, ?string $key = null): array
    {
        if (is_a($value, Collection::class)) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            /*
             * Some keys' contents are not transformed
             */
            if (!in_array($key, ['error', 'errors'], true)) {
                $value = collect($value)->mapWithKeys(fn ($value, $key) => $this->version1Formatted($value, (string) $key))->toArray();
            }
        }

        if ($key === null) {
            return $value;
        }

        /*
         * v1 expects json keys and trailing IDs to be capitalised
         */
        $key = str_replace(
            [
                'username',
                'memoryNotes',
                'systemId',
                'systemName',
                'inGame',
                'id',
                'Id',
                'pointsTotal',
                'unreadMessagesCount',
            ],
            [
                'User',
                'CodeNotes',
                'ConsoleID',
                'ConsoleName',
                'Ingame',
                'ID',
                'ID',
                'Score',
                'Messages',
            ],
            $key
        );
        $key = ucfirst($key);

        return [$key => $value];
    }
}
