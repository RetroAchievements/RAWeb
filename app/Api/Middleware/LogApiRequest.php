<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use App\Models\ApiLogEntry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogApiRequest
{
    public function __construct(
        private readonly string $apiVersion = 'v2',
    ) {
    }

    public function handle(Request $request, Closure $next, ?string $apiVersion = null): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $user = $request->user();
        if ($user === null) {
            return $response;
        }

        // Compute, but stash, the payload. We'll write the payload in
        // `terminate()` so it stays off the request's latency path.
        $request->attributes->set('apiLogPayload', [
            'apiVersion' => $apiVersion ?? $this->apiVersion,
            'userId' => $user->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'responseCode' => $response->getStatusCode(),
            'responseTimeMs' => $this->calculateResponseTime($startTime),
            'responseSizeBytes' => $this->calculateResponseSize($response),
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'requestData' => $this->sanitizeRequestData($request),
            'errorMessage' => $this->getErrorMessage($response),
        ]);

        return $response;
    }

    /**
     * Persist the log entry after the response has been sent to the client, so
     * the database write is off the request's latency path.
     */
    public function terminate(Request $request, Response $response): void
    {
        /** @var array<string, mixed>|null $payload */
        $payload = $request->attributes->get('apiLogPayload');
        if ($payload === null) {
            return;
        }

        try {
            ApiLogEntry::logRequest(
                $payload['apiVersion'],
                $payload['userId'],
                $payload['endpoint'],
                $payload['method'],
                $payload['responseCode'],
                $payload['responseTimeMs'],
                $payload['responseSizeBytes'],
                $payload['ipAddress'],
                $payload['userAgent'],
                $payload['requestData'],
                $payload['errorMessage'],
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function calculateResponseTime(float $startTime): int
    {
        return (int) round((microtime(true) - $startTime) * 1000);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sanitizeRequestData(Request $request): ?array
    {
        $data = $request->all();

        if (empty($data)) {
            return null;
        }

        $sensitiveFields = ['password', 'token', 'api_key', 'secret'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    private function getErrorMessage(Response $response): ?string
    {
        if ($response->getStatusCode() < 400) {
            return null;
        }

        $content = json_decode($response->getContent(), true);

        return $content['error'] ?? $content['message'] ?? null;
    }

    private function calculateResponseSize(Response $response): ?int
    {
        $content = $response->getContent();
        if ($content === false) {
            return null;
        }

        return strlen($content);
    }
}
