<?php

declare(strict_types=1);

namespace App\Api\Controllers;

use App\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TODO: Replaced by [retroachievements/retroachievements-api-php](https://github.com/retroachievements/retroachievements-api-php)
 */
class WebApiV1Controller extends Controller
{
    public function noop(Request $request, ?string $method = null): void
    {
        abort(405, 'Method not allowed');
    }

    /**
     * RPC-style endpoint(s) that users' client implementations rely on
     * An official single-file client is available as download.
     */
    public function request(Request $request, string $method): JsonResponse
    {
        return response()->json([
            'success' => false,
            'method' => $method,
            'request' => $request->input(),
        ], 501);
    }

    public function connectServers(Request $request): JsonResponse
    {
        /*
         * TODO
         */
        return response()->json([
            // 'method' => $method,
            'data' => $request->input(),
        ], 501);
    }

    public function users(Request $request): JsonResponse
    {
        /*
         * TODO
         */
        return response()->json([
            // 'method' => $method,
            'data' => $request->input(),
        ], 501);
    }
}
