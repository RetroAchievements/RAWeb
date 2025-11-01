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
        FetchDynamicShortcodeContentAction $action,
    ): JsonResponse {
        $entities = $action->execute(
            achievementIds: $request->input('achievementIds'),
            eventIds: $request->input('eventIds'),
            gameIds: $request->input('gameIds'),
            hubIds: $request->input('hubIds'),
            setIds: $request->input('setIds'),
            ticketIds: $request->input('ticketIds'),
            usernames: $request->input('usernames'),
        );

        return response()->json($entities);
    }
}
