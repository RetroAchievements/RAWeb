<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Community\Enums\ActivityType;
use App\Community\Models\UserActivity;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Models\Game;
use Exception;
use Illuminate\Http\Request;

trait HeartbeatRequests
{
    /**
     * Used by RAIntegration
     * Sends out events
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

        /*
         * any activity event will update the last_activity_at timestamp on the user
         */
        if ($activityType === ActivityType::StartedPlaying) {
            /** @var ?Game $game */
            $game = Game::find($messagePayload);
            if ($game) {
                /** @var ResumePlayerSessionAction $resumePlayerSessionAction */
                $resumePlayerSessionAction = app()->make(ResumePlayerSessionAction::class);
                $resumePlayerSessionAction->execute($request, $game);
            }
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

        // if (isset($activityMessage)) {
        //     UpdateUserRichPresence($user, $gameID, $activityMessage);
        // }

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

        // TODO: should be $request->user('connect-token')->games()->find($gameId)
        /** @var ?Game $game */
        $game = Game::find($gameId);
        abort_if($game === null, 404, 'Game with ID "' . $gameId . '" not found');
        /**
         * TODO: abort if game has not yet been assigned to user
         */
        // abort_unless($game !== null, 404, 'Game with ID "' . $gameId . '" is not attached to this user');

        $richPresence = $request->input('m');

        /**
         * TODO: pass game hash here if set
         */
        /** @var ResumePlayerSessionAction $resumePlayerSessionAction */
        $resumePlayerSessionAction = app()->make(ResumePlayerSessionAction::class);
        $resumePlayerSessionAction->execute($request, $game, null, $richPresence);

        return [];
    }
}
