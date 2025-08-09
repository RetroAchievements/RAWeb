<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebApiController extends Controller
{
    public function noop(Request $request, ?string $method = null): void
    {
        abort(405, 'Method not allowed');
    }

    public function connectServers(Request $request): JsonResponse
    {
        // TODO JSON:API response

        return response()->json([
            // 'method' => $method,
            'data' => $request->input(),
        ], 501);
    }

    public function users(Request $request): JsonResponse
    {
        // TODO JSON:API response

        return response()->json([
            // 'method' => $method,
            'data' => $request->input(),
        ], 501);
    }
}
