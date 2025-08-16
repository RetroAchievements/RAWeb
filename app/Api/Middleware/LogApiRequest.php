<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use App\Models\ApiLogEntry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function __construct(
        private readonly string $apiVersion = 'v2'
    ) {
    }

    public function handle(Request $request, Closure $next, ?string $apiVersion = null): Response
    {
        $startTime = microtime(true);
        $version = $apiVersion ?? $this->apiVersion;

        $response = $next($request);

        ApiLogEntry::logRequest(
            $version,
            $request->user()?->id,
            $request->path(),
            $request->method(),
            $response->getStatusCode(),
            $this->calculateResponseTime($startTime),
            $this->calculateResponseSize($response),
            $request->ip(),
            $request->userAgent(),
            $this->sanitizeRequestData($request),
            $this->getErrorMessage($response)
        );

        return $response;
    }

    private function calculateResponseTime(float $startTime): int
    {
        return (int) round((microtime(true) - $startTime) * 1000);
    }

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
