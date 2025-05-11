<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\UserData;
use App\Http\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('q', '');

        // Searches must be at least 3 characters long.
        if (mb_strlen($keyword) < 3) {
            return response()->json([]);
        }

        $users = User::sqlSearch($keyword)->get();

        $mappedUsers = $users->map(fn ($user) => UserData::fromUser($user));

        return response()->json(['users' => $mappedUsers]);
    }
}
