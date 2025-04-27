<?php

declare(strict_types=1);

namespace App\Connect\Commands;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiHandlerBase
{
    abstract public function initialize(Request $request): ?array;

    abstract public function process(): array;

    public function execute(Request $request): JsonResponse
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

    protected function accessDenied(): array
    {
        return [
            'Success' => false,
            'Status' => 403,
            'Code' => 'access_denied',
            'Error' => 'Access denied.',
        ];
    }

    protected function gameNotFound(): array
    {
        return [
            'Success' => false,
            'Status' => 404,
            'Code' => 'not_found',
            'Error' => 'Unknown game',
        ];
    }
}
