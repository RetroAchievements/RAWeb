<?php

declare(strict_types=1);

namespace App\Api\Controllers;

use App\Http\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
