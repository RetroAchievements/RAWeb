<?php

declare(strict_types=1);

namespace App\Api\Controllers;

use App\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TODO: Implement V1 in [retroachievements/api-php](https://github.com/retroachievements/api-php)
 */
class WebApiV1Controller extends Controller
{
    // TODO refactor public/API/API_*

    public function request(Request $request, string $method): JsonResponse
    {
        return response()->json([
            'method' => $method,
            'request' => $request->input(),
        ], 501);
    }
}
