<?php

declare(strict_types=1);

namespace App\Api\Controllers;

use App\Api\V2\Data\HealthData;
use App\Http\Controller;
use Illuminate\Http\JsonResponse;
use LaravelJsonApi\OpenApiSpec\Attributes\WithDescription;

class HealthController extends Controller
{
    #[WithDescription(HealthData::class, 'Check that the API responds')]
    public function check(): JsonResponse
    {
        return response()->json(
            new HealthData(status: 'ok', timestamp: now()->toImmutable()),
        );
    }
}
