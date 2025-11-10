<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Contracts\HasViewTracking;
use App\Http\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ViewableApiController extends Controller
{
    /**
     * Mark that a given user has viewed a given "viewable" item.
     */
    public function store(string $viewableType, int $viewableId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Resolve the viewable model from the morph map.
        $morphMap = Relation::$morphMap;
        if (!isset($morphMap[$viewableType])) {
            return response()->json(['error' => 'Invalid viewable type'], 400);
        }

        $modelClass = $morphMap[$viewableType];
        $viewable = $modelClass::find($viewableId);

        if (!$viewable) {
            return response()->json(['error' => 'Viewable not found'], 404);
        }

        if (!$viewable instanceof HasViewTracking) {
            return response()->json(['error' => 'This model does not support views'], 400);
        }

        $viewable->markAsViewedBy($user);

        return response()->json(['success' => true]);
    }
}
