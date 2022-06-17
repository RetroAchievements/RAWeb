<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Events\PlayerSessionResumed;
use App\Platform\Events\PlayerSessionStarted;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\PlayerSession;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ResumePlayerSessionAction
{
    public function execute(Request $request, Game $game, ?GameHash $gameHash = null, ?string $presence = null): void
    {
        /** @var ?User $user */
        $user = $request->user();

        abort_unless($user !== null, 401);

        $attachPlayerGameAction = app()->make(AttachPlayerGame::class);
        $attachPlayerGameAction->execute($user, $game);

        $now = Carbon::now();

        // look for an active session
        /** @var ?PlayerSession $playerSession */
        $playerSession = $user->playerSessions()->where('game_id', $game->id)->first();
        if ($playerSession) {
            // if the session hasn't been updated in the last 10 minutes, start a new session
            if ($now->diffInMinutes($playerSession->updated_at) < 10) {
                $playerSession->duration = $now->diffInMinutes($playerSession->created_at);

                if ($presence) {
                    $playerSession->rich_presence = $presence;
                    $playerSession->rich_presence_updated_at = $now;
                }

                $playerSession->save();

                PlayerSessionResumed::dispatch($user, $game, $presence);

                return;
            }
        }

        // provide a default presence for the new session if one was not provided
        if (!$presence) {
            $presence = 'Playing ' . $game->title;
        }

        // create new session
        $user->playerSessions()->save(
            new PlayerSession([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'game_hash_id' => $game->gameHashSets()->first()->hashes()->first()->id, // TODO
                'game_hash_set_id' => $game->gameHashSets()->first()->id, // TODO
                'rich_presence' => $presence,
                'rich_presence_updated_at' => $now,
                'duration' => 0,
            ]),
        );

        // TODO: store user agent

        PlayerSessionStarted::dispatch($user, $game, $presence);
    }
}
