<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Actions\FetchDynamicShortcodeContentAction;
use App\Community\Requests\PreviewShortcodeBodyRequest;
use App\Http\Controller;
use Illuminate\Http\JsonResponse;

class ShortcodeApiController extends Controller
{
    public function preview(
        PreviewShortcodeBodyRequest $request,
        FetchDynamicShortcodeContentAction $action
    ): JsonResponse {
        $entities = $action->execute(
            usernames: $request->input('usernames'),
            ticketIds: $request->input('ticketIds'),
            achievementIds: $request->input('achievementIds'),
            gameIds: $request->input('gameIds'),
            hubIds: $request->input('hubIds'),
        );

        return response()->json($entities);
    }
}
