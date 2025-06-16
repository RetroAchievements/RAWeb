<?php

declare(strict_types=1);

namespace App\Connect\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiAction
{
    abstract protected function initialize(Request $request): ?array;

    abstract protected function process(): array;

    public function handleRequest(Request $request): JsonResponse
    {
        $result = $this->initialize($request);

        if (!$result) {
            $result = $this->process();
        }

        return $this->buildResponse($result);
    }

    protected function buildResponse(array $result): JsonResponse
    {
        $status = $result['Status'] ?? 200;
        $response = response()->json($result, $status);

        $response->header('Content-Length', (string) strlen($response->getContent()));

        if ($status === 401) {
            $response->header('WWW-Authenticate', 'Bearer');
        }

        return $response;
    }

    protected function missingParameters(): array
    {
        return [
            'Success' => false,
            'Status' => 400,
            'Code' => 'missing_parameter',
            'Error' => 'One or more required parameters is missing.',
        ];
    }

    protected function invalidParameter(string $message): array
    {
        return [
            'Success' => false,
            'Status' => 400,
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

    protected function gameNotFound(): array
    {
        return [
            'Success' => false,
            'Status' => 404,
            'Code' => 'not_found',
            'Error' => 'Unknown game.',
        ];
    }

    protected function achievementNotFound(): array
    {
        return [
            'Success' => false,
            'Status' => 404,
            'Code' => 'not_found',
            'Error' => 'Unknown achievement.',
        ];
    }
}
