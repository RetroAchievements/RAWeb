<?php

declare(strict_types=1);

namespace App\Connect\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiAction
{
    protected ?string $userAgent = null;
    protected ?string $ipAddress = null;

    abstract protected function initialize(Request $request): ?array;

    abstract protected function process(): array;

    public function handleRequest(Request $request): JsonResponse
    {
        $this->userAgent = $request->header('User-Agent');
        $this->ipAddress = $request->ip();

        $result = $this->initialize($request);

        if (!$result) {
            $result = $this->process();
        }

        return $this->buildResponse($result);
    }

    protected function buildResponse(array $result): JsonResponse
    {
        $status = $result['Status'] ?? 200;

        // Convert the response to a JSON string in order to calculate the exact Content-Length.
        // This also sets the Content-Type header to application/json.
        $response = response()->json($result, $status);

        // Cloudflare is manipulating the headers of dorequest.php responses, and some clients
        // are unable to gracefully handle this (ie: RetroArch 1.20.0 and below). By adding
        // explicit Content-Type, Content-Length, and Cache-Control headers, we inform Cloudflare
        // that these responses are immutable and should be passed straight through.
        $response->header('Content-Length', (string) strlen($response->getContent()));
        $response->header('Cache-Control', 'no-transform, private, must-revalidate');

        if ($status === 401) {
            $response->header('WWW-Authenticate', 'Bearer');
        }

        return $response;
    }

    protected function missingParameters(): array
    {
        return [
            'Success' => false,
            'Status' => 422,
            'Code' => 'missing_parameter',
            'Error' => 'One or more required parameters is missing.',
        ];
    }

    protected function invalidParameter(string $message): array
    {
        return [
            'Success' => false,
            'Status' => 422,
            'Code' => 'invalid_parameter',
            'Error' => $message,
        ];
    }

    protected function accessDenied(string $message = 'Access denied.'): array
    {
        return [
            'Success' => false,
            'Status' => 403,
            'Code' => 'access_denied',
            'Error' => $message,
        ];
    }

    protected function unsupportedClient(): array
    {
        return [
            'Success' => false,
            'Status' => 403,
            'Code' => 'unsupported_client',
            'Error' => 'This client is not supported.',
        ];
    }

    protected function unsupportedSystem(string $message): array
    {
        return [
            'Success' => false,
            'Status' => 403,
            'Code' => 'unsupported_system',
            'Error' => $message,
        ];
    }

    protected function resourceNotFound(string $resourceType): array
    {
        return [
            'Success' => false,
            'Status' => 404,
            'Code' => 'not_found',
            'Error' => "Unknown $resourceType.",
        ];
    }

    protected function gameNotFound(): array
    {
        return $this->resourceNotFound('game');
    }

    protected function internalError(string $message): array
    {
        return [
            'Success' => false,
            'Status' => 500,
            'Code' => 'internal_error',
            'Error' => $message,
        ];
    }
}
