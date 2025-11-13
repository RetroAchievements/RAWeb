<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Actions\FetchDynamicShortcodeContentAction;
use App\Community\Requests\PreviewShortcodeBodyRequest;
use App\Http\Controller;
use App\Support\Shortcode\Shortcode;
use Illuminate\Http\JsonResponse;

class ShortcodeApiController extends Controller
{
    public function preview(
        PreviewShortcodeBodyRequest $request,
        FetchDynamicShortcodeContentAction $action,
    ): JsonResponse {
        $body = $request->input('body');

        // Normalize URLs to shortcode format (eg: "https://retroachievements.org/game/123" -> "[game=123]").
        $body = normalize_shortcodes($body);

        // Convert [game=X?set=Y] shortcodes to their backing game IDs.
        $body = Shortcode::convertGameSetShortcodesToBackingGame($body);

        // Extract entity IDs from the normalized+converted body.
        $extractedIds = Shortcode::extractShortcodeIds($body);

        // Fetch the entities and return the final converted body.
        $entities = $action->execute(
            convertedBody: $body,
            achievementIds: $extractedIds['achievementIds'],
            eventIds: $extractedIds['eventIds'],
            gameIds: $extractedIds['gameIds'],
            hubIds: $extractedIds['hubIds'],
            ticketIds: $extractedIds['ticketIds'],
            usernames: $extractedIds['usernames'],
        );

        return response()->json($entities);
    }
}
