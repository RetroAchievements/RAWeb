<?php

namespace App\Http\Controllers\Api;

use App\Http\Controller;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', News::class);

        return response()->json([]);
    }
}
