<?php

declare(strict_types=1);

namespace App\Api\Controllers;

use App\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;

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

    public function requestWebApiKey(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            Fortify::username() => $request->input('username'),
            'password' => $request->input('password'),
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            return response()->json(['webApiKey' => $user->api_token]);
        }

        return response()->json(['error' => 'Something went wrong'], 400);
    }
}
