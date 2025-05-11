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

        $searchResults = User::search($keyword)
            ->where('deleted_at', null)
            ->where('banned_at', null)
            ->orderBy('last_activity_at', 'desc')
            ->take(20)
            ->get();

        $filteredUsers = $searchResults
            // Scout's query builder doesn't let us do a "not null" filter.
            ->filter(fn ($user) => $user->email_verified_at !== null)
            ->take(10);

        $mappedUsers = $filteredUsers->map(fn ($user) => UserData::fromUser($user));

        return response()->json(['users' => $mappedUsers]);
    }
}
