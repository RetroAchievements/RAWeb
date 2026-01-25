<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use Illuminate\Http\JsonResponse;

/**
 * @deprecated
 * 
 * This action provides support for the legacy API function used to login.
 * New clients should use ?r=login2 instead (available since rcheevos 11.0) which
 * provides HTTP status codes as part of the response.
 *
 * This endpoint must be maintained indefinitely for backwards compatibility with:
 * - RetroArch versions prior to 1.17.0.
 * - DLL integrations older than 1.3.
 * - Other legacy clients that haven't migrated to rc_client.
 */
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
