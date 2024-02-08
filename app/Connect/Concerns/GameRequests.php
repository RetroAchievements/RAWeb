<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Models\Game;
use App\Models\GameHash;
use App\Models\GameHashSet;
use App\Platform\Actions\ResumePlayerSession;
use Exception;
use Illuminate\Http\Request;

trait GameRequests
{
    /**
     * Used by RAIntegration
     * Called on game load
     * Has to be public for RetroArch
     *
     * @throws Exception
     *
     * @since 1.0
     */
    protected function gameidMethod(Request $request): array
    {
        /*
         * NOTE: has to be public for RetroArch
         * // $this->authorize('viewAny', Game::class);
         */
        $request->validate(
            [
                'm' => 'required|string',
            ],
            $messages = [],
            $attributes = [
                'm' => 'Hash',
            ]
        );

        $gameHashMd5 = $request->input('m');

        /** @var ?GameHash $gameHash */
        $gameHash = GameHash::where('hash', $gameHashMd5)
            ->with('gameHashSets.game')
            ->first();

        if ($gameHash === null) {
            return [
                'gameId' => 0,
            ];
        }

        /** @var ?GameHashSet $gameHashSet */
        $gameHashSet = $gameHash->gameHashSets->first();

        if ($gameHashSet === null) {
            return [
                'gameId' => 0,
            ];
        }

        /** @var ?Game $game */
        $game = $gameHashSet->game;

        /*
         * Abort if the user is not permitted to create Games
         * Update: not desired - reverting back to how it used to work
         * Non-devs should still be able to test compatibility
         */
        // if (!$request->user('connect-token') || $request->user('connect-token')->cannot('create', Game::class)) {
        //     abort_unless($gameHash !== null, 404, __('Game for MD5 hash ":hash" not found', ['hash' => $gameHashMd5]));
        //     abort_unless($firstLinkedGame !== null, 404, __('No game associated with MD5 hash ":hash"', ['hash' => $gameHashMd5]));
        // }

        /*
         * RA_Integration expects a success response with game id 0 if no hash association was found
         * this will trigger the link dialog
         */
        if ($game === null) {
            return [
                'gameId' => 0,
            ];
        }

        // NOTE: checking for a game id by hash might be done by tools as well
        // this endpoint is sometimes retried in quick succession, too
        try {
            /** @var ResumePlayerSession $resumePlayerSessionAction */
            $resumePlayerSessionAction = app()->make(ResumePlayerSession::class);
            $resumePlayerSessionAction->execute($request->user('connect-token'), $game, $gameHash);
        } catch (Exception) {
            // fail silently - might be an unauthenticated request (RetroArch)
        }

        return [
            'gameId' => $game->id,
        ];
    }

    /**
     * Used by RAIntegration
     * Called on game load
     *
     * @since 1.0
     */
    protected function patchMethod(Request $request): array
    {
        $this->authorize('viewAny', Game::class);

        /*
         * TODO: build for V2 with prettier output - somewhere else
         */
        // if ($this->acceptVersion !== 1) {
        //     abort(501);
        // }

        $request->validate(
            [
                'g' => 'required|integer',
            ],
            $messages = [],
            $attributes = [
                'g' => 'Game ID',
            ]
        );

        $gameId = $request->input('g');

        /** @var ?Game $game */
        $game = Game::with([
            'system',
            'leaderboards' => function ($query) {
                /*
                 * TODO: get leaderboards
                 */
            },
            'achievements' => function ($query) {
                /*
                 * TODO: get achievements
                 */
            },
        ])->find($gameId);
        abort_if($game === null, 404, 'Game with ID "' . $gameId . '" not found');

        // achievements
        // $flag = seekPOSTorGET( 'f', 0, 'integer' );
        // $hardcore = seekPOSTorGET( 'h', 0, 'integer' );
        // $response[ 'PatchData' ] = GetPatchData( $gameID, $flag, $user, $hardcore );
        // $flagsCond = "TRUE";
        // if ($flag != 0) {
        //     $flagsCond = "Flags='$flag'";
        // }
        // $query = "SELECT ID, MemAddr, Title, Description, Points, Author, UNIX_TIMESTAMP(DateModified) AS Modified, UNIX_TIMESTAMP(DateCreated) AS Created, BadgeName, Flags
        //       FROM Achievements
        //       WHERE GameID='$gameID' AND $flagsCond
        //       ORDER BY DisplayOrder";
        // settype($db_entry['ID'], 'integer');
        // settype($db_entry['Points'], 'integer');
        // settype($db_entry['Modified'], 'integer');
        // settype($db_entry['Created'], 'integer');
        // settype($db_entry['Flags'], 'integer');

        // leaderboards
        // $lbData = array();
        // //    Always append LBs?
        // $query = "SELECT ld.ID, ld.Mem, ld.Format, ld.Title, ld.Description
        //       FROM LeaderboardDef AS ld
        //       WHERE ld.GameID = $gameID
        //       ORDER BY ld.DisplayOrder, ld.ID ";
        // $dbResult = s_mysql_query($query);
        // if ($dbResult !== false) {
        //     while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        //         settype($db_entry['ID'], 'integer');
        //         $lbData[] = $db_entry;
        //     }

        /** @var ?GameHashSet $gameHashSet */
        $gameHashSet = $game->gameHashSets()->compatible()->first();
        abort_if($gameHashSet === null, 404, 'Game with ID "' . $gameId . '" has no hash sets');

        $gameHashSet->load('trigger');

        $richPresenceTrigger = null;
        if ($gameHashSet->trigger) {
            $richPresenceTrigger = $gameHashSet->trigger->conditions;
        }

        // TODO: check if returning null is valid
        // NOTE: V1 wants a relative path and the file has to be within Images/ without any further subdirectories
        $imageIcon = $game->hasMedia('icon')
            ? '/Images/' . $game->getFirstMedia('icon')->getCustomProperty('sha1') . '.png'
            : '/Images/000001.png';

        /*
         * Full V1 response
         */

        return [
            'patchData' => [
                'id' => $game->id,
                'title' => $game->title,
                'systemId' => $game->system->id,
                'systemName' => $game->system->name,

                /*
                 * The file has to be in /Images but the name may differ
                 */
                'imageIcon' => $imageIcon,

                'richPresencePatch' => $richPresenceTrigger,

                'achievements' => [],
                // 'achievements' => $game->achievements->makeHidden('game')->keyBy('id'),
                'leaderboards' => [],
                // 'leaderboards' => $game->leaderboards,

                // 'flags' => $game->status_flag,
                // 'isFinal' => $game->final,

                // 'forumTopicId' => $game->forum_topic_id,
                // 'imageTitle' => $game->image_title,
                // 'imageInGame' => $game->image_in_game,
                // 'imageBoxArt' => $game->image_box_art,
                // 'publisher' => $game->publisher,
                // 'developer' => $game->developer,
                // 'genre' => $game->genre,
                // 'released' => $game->release,
            ],
        ];
    }
}
