<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Community\Enums\ActivityType;
use App\Community\Models\UserActivity;
use App\Platform\Events\PlayerSessionHeartbeat;
use App\Platform\Models\Game;
use Exception;
use Illuminate\Http\Request;

trait HeartbeatRequests
{
    /**
     * Used by RAIntegration
     * Called on
     * - game load -> StartedPlaying
     *
     * @throws Exception
     *
     * @since 1.0
     */
    protected function postactivityMethod(Request $request): array
    {
        $this->authorize('create', UserActivity::class);

        $request->validate(
            [
                'a' => 'required|integer', // activity id
                'm' => 'required', // mixed message payload
            ],
            $messages = [],
            $attributes = [
                'a' => 'Activity Type ID',
                'm' => 'Message',
            ]
        );

        $activityType = $request->input('a');
        $messagePayload = $request->input('m');

        // Behave like ping, ignore the rest
        if ($activityType === ActivityType::StartedPlaying) {
            PlayerSessionHeartbeat::dispatch($request->user('connect-token')->id, (int) $messagePayload);
        }

        return [];
    }

    /**
     * Used by RAIntegration
     * Called in an interval while game is running
     *
     * @throws Exception
     *
     * @since 1.0
     */
    protected function pingMethod(Request $request): array
    {
        $this->authorize('create', UserActivity::class);

        $request->validate(
            [
                'g' => 'required|integer',
                'm' => 'string',
            ],
            $messages = [],
            $attributes = [
                'g' => 'Game ID',
                'm' => 'Activity Message',
            ]
        );

        $gameId = $request->input('g');
        $richPresence = $request->input('m');

        // TODO: pass game hash here if set

        PlayerSessionHeartbeat::dispatch($request->user('connect-token')->id, $gameId, $richPresence);

        return [];
    }
}
