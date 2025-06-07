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
        if (array_key_exists('Status', $result)) {
            $status = $result['Status'];
            if ($status === 401) {
                return response()->json($result, $status)->header('WWW-Authenticate', 'Bearer');
            }

            return response()->json($result, $status);
        }

        return response()->json($result);
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
}
