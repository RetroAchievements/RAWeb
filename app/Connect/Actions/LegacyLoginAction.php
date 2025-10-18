<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use Illuminate\Http\JsonResponse;

class LegacyLoginAction extends LoginAction
{
    protected function buildResponse(array $result): JsonResponse
    {
        $response = parent::buildResponse($result);

        // do not return $response['Status'] as an HTTP status code when using this
        // endpoint. legacy clients sometimes report the HTTP status code instead of
        // the $response['Error'] message.
        $response->setStatusCode(200);

        return $response;
    }
}
