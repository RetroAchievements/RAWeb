<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Models\News;
use Illuminate\Http\Request;
use App\Http\Controller;

class NewsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', News::class);

        return response()->json([]);
    }
}