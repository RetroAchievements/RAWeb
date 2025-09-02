<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Http\Controller;
use App\Mail\Services\UnsubscribeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnsubscribeApiController extends Controller
{
    public function __construct(
        private UnsubscribeService $unsubscribeService
    ) {
    }

    public function undo(Request $request, string $token): JsonResponse
    {
        $result = $this->unsubscribeService->processUndo($token);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errorCode' => $result['errorCode'] ?? 'unknown',
            ], 400);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
