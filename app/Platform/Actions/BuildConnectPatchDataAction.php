<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Enums\ClientSupportLevel;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

// TODO move to app/Connect/Actions
// TODO more tests

// OPEN QUESTION when given a GameHash, should the root Achievements & Leaderboards be returned as `null`?

class BuildConnectPatchDataAction
{
    /**
     * Assembles a patch data package of all components needed by emulators:
     * - Basic game information (title, system, etc.)
     * - Achievement definitions and unlock conditions
     * - Leaderboard configurations
     * - Rich presence script
     *
     * Modern rcheevos integrations send the game hash. Legacy integrations send only
     * the game. We need to support constructing the patch data package for both situations.
     *
     * @param GameHash|null $gameHash The game hash to build patch data for
     * @param Game|null $game The game to build patch data for
     * @param User|null $user The current user requesting the patch data (for player count calculations)
     * @param AchievementFlag|null $flag Optional flag to filter the achievements by (eg: only official achievements)
     * @param ClientSupportLevel $clientSupportLevel the current support level of the user's client/emulator
     * @throws InvalidArgumentException when neither $gameHash nor $game is provided
     */
    public function execute(
        ClientSupportLevel $clientSupportLevel,
        ?GameHash $gameHash = null,
        ?Game $game = null,
        ?User $user = null,
        ?AchievementFlag $flag = null,
    ): array {
        if (!$gameHash && !$game) {
            throw new InvalidArgumentException('Either gameHash or game must be provided to build patch data.');
        }

        $coreGame = $gameHash->game ?? $game;
        $coreAchievementSet = GameAchievementSet::where('game_id', $coreGame->id)
            ->core()
            ->with('achievementSet.achievements.developer')
            ->first();

        $gamePlayerCount = $this->calculateGamePlayerCount($coreGame, $user);

        return [
            'Success' => true,
            'PatchData' => [
                ...$this->buildBaseGameData($coreGame),

                'Achievements' => $coreAchievementSet
                    ? $this->buildAchievementsData(
                        $coreAchievementSet,
                        $gamePlayerCount,
                        $flag,
                        $clientSupportLevel,
                        canAddWarningAchievement: $game->achievements_published < 0 // will never be true. change to > when ready
                    )
                    : [],

                'Leaderboards' => $this->buildLeaderboardsData($coreGame),

                // Don't even send a 'Sets' value to legacy clients.
                ...($gameHash ? ['Sets' => $this->buildSetsData($gameHash, $user, $gamePlayerCount, $flag)] : []),
            ],
        ];
    }

    /**
     * Builds achievement set data for multiset support.
     *
     * @param GameHash|null $gameHash The game hash to build set data for
     * @param User|null $user The current user requesting the data
     * @param int $gamePlayerCount Total player count for rarity calculations
     * @param AchievementFlag|null $flag Optional flag to filter the achievements by (eg: only official achievements)
     */
    private function buildSetsData(
        ?GameHash $gameHash,
        ?User $user,
        int $gamePlayerCount,
        ?AchievementFlag $flag,
    ): array {
        if (!$gameHash || !$user) {
            return [];
        }

        $resolvedSets = (new ResolveAchievementSetsAction())->execute($gameHash, $user);

        // Don't fetch the games in the loop. Grab them all in a single query.
        $coreGameIds = $resolvedSets->pluck('core_game_id')->unique();
        $games = Game::whereIn('ID', $coreGameIds)->get()->keyBy('ID');

        $sets = [];
        foreach ($resolvedSets as $resolvedSet) {
            $setGame = $games[$resolvedSet->core_game_id];

            $sets[] = [
                'GameAchievementSetID' => $resolvedSet->id,
                'CoreGameID' => $resolvedSet->core_game_id,
                'Title' => $resolvedSet->title,
                'Type' => $resolvedSet->type->value,
                'ImageIcon' => $setGame->ImageIcon,
                'ImageIconURL' => media_asset($setGame->ImageIcon),
                'Achievements' => $this->buildAchievementsData($resolvedSet, $gamePlayerCount, $flag, null),
                'Leaderboards' => $this->buildLeaderboardsData($setGame),
            ];
        }

        return $sets;
    }

    /**
     * Builds achievement information needed by emulators.
     *
     * @param GameAchievementSet $gameAchievementSet The achievement set to build achievement data for
     * @param int $gamePlayerCount The total number of players (minimum of 1 to prevent division by zero)
     * @param AchievementFlag|null $flag Optional flag to filter the achievements by (eg: only official achievements)
     * @param ClientSupportLevel|null $clientSupportLevel the current support level of the user's client/emulator
     * @param bool $canAddWarningAchievement whether or not a warning achievement can be inserted at the top of the achievements list
     */
    private function buildAchievementsData(
        GameAchievementSet $gameAchievementSet,
        int $gamePlayerCount,
        ?AchievementFlag $flag,
        ?ClientSupportLevel $clientSupportLevel,
        bool $canAddWarningAchievement = false
    ): array {
        /** @var Collection<int, Achievement> $achievements */
        $achievements = $gameAchievementSet->achievementSet
            ->achievements()
            ->with('developer')
            ->orderBy('DisplayOrder') // explicit display order
            ->orderBy('ID')           // tiebreaker on creation sequence
            ->get();

        if ($flag) {
            $achievements = $achievements->where('Flags', '=', $flag->value);
        }

        $achievementsData = [];

        if (!is_null($clientSupportLevel) && $clientSupportLevel !== ClientSupportLevel::Full) {
            if ($canAddWarningAchievement) {
                // We intentionally place the warning achievement at the top of the list.
                $achievementsData[] = $this->buildClientSupportWarningAchievement($clientSupportLevel);
            }
        }

        foreach ($achievements as $achievement) {
            // If an achievement has an invalid flag, skip it.
            if (!AchievementFlag::tryFrom($achievement->Flags)) {
                continue;
            }

            // Calculate rarity assuming it will be used when the player unlocks the achievement,
            // which implies they haven't already unlocked it.
            $rarity = min(100.0, round((float) ($achievement->unlocks_total + 1) * 100 / $gamePlayerCount, 2));
            $rarityHardcore = min(100.0, round((float) ($achievement->unlocks_hardcore_total + 1) * 100 / $gamePlayerCount, 2));

            $achievementsData[] = [
                'ID' => $achievement->id,
                'MemAddr' => $achievement->MemAddr,
                'Title' => $achievement->title,
                'Description' => $achievement->description,
                'Points' => $achievement->points,
                'Author' => $achievement->developer->display_name ?? '',
                'Modified' => $achievement->DateModified->unix(),
                'Created' => $achievement->DateCreated->unix(),
                'BadgeName' => $achievement->BadgeName,
                'Flags' => $achievement->Flags,
                'Type' => $achievement->type,
                'Rarity' => $rarity,
                'RarityHardcore' => $rarityHardcore,
                'BadgeURL' => $achievement->badge_unlocked_url,
                'BadgeLockedURL' => $achievement->badge_locked_url,
            ];
        }

        return $achievementsData;
    }

    /**
     * This warning achievement should appear at the top of the emulator's achievements
     * list. It should automatically unlock after a few seconds of patch data retrieval.
     * The intention is to notify a user that they are using an outdated client
     * and need to update, as well as what the repercussions of their continued
     * play session with their current client might be.
     */
    private function buildClientSupportWarningAchievement($clientSupportLevel): array
    {
        return [
            'ID' => Achievement::CLIENT_WARNING_ID,
            'MemAddr' => '1=1.300.', // pop after 5 seconds
            'Title' => ($clientSupportLevel === ClientSupportLevel::Outdated) ?
                'Warning: Outdated Emulator (please update)' : 'Warning: Unknown Emulator',
            'Description' => 'Hardcore unlocks cannot be earned using this emulator.',
            'Points' => 0,
            'Author' => '',
            'Modified' => Carbon::now()->unix(),
            'Created' => Carbon::now()->unix(),
            'BadgeName' => '00000',
            'Flags' => AchievementFlag::OfficialCore->value,
            'Type' => null,
            'Rarity' => 0.0,
            'RarityHardcore' => 0.0,
            'BadgeURL' => media_asset("Badge/00000.png"),
            'BadgeLockedURL' => media_asset("Badge/00000_lock.png"),
        ];
    }

    /**
     * Builds the basic game information needed by emulators.
     */
    private function buildBaseGameData(Game $game): array
    {
        return [
            'ID' => $game->id,
            'Title' => $game->title,
            'ImageIcon' => $game->ImageIcon,
            'RichPresencePatch' => $game->RichPresencePatch,
            'ConsoleID' => $game->ConsoleID,
            'ImageIconURL' => media_asset($game->ImageIcon),
        ];
    }

    /**
     * Builds leaderboard information needed by emulators.
     */
    private function buildLeaderboardsData(Game $game): array
    {
        $leaderboardsData = [];

        // TODO detach leaderboards from games
        $leaderboards = $game->leaderboards()
            ->orderBy('DisplayOrder') // explicit display order
            ->orderBy('ID')           // tiebreaker on creation sequence
            ->get();

        foreach ($leaderboards as $leaderboard) {
            $leaderboardsData[] = [
                'ID' => $leaderboard->id,
                'Mem' => $leaderboard->Mem,
                'Format' => $leaderboard->Format,
                'LowerIsBetter' => $leaderboard->LowerIsBetter,
                'Title' => $leaderboard->title,
                'Description' => $leaderboard->Description,
                'Hidden' => ($leaderboard->DisplayOrder < 0),
            ];
        }

        return $leaderboardsData;
    }

    /**
     * Calculates the total number of players for the game, which ultimately gets used in
     * achievement rarity calculations.
     *
     * This method adds 1 to the total if the requesting user hasn't played the game yet,
     * which ensures accurate rarity predictions for when they unlock achievements.
     *
     * @param Game $game The game to calculate player count for
     * @param User|null $user The current user requesting the data
     *
     * @return int The total number of players (minimum of 1 to prevent division by zero)
     */
    private function calculateGamePlayerCount(Game $game, ?User $user): int
    {
        $gamePlayerCount = $game->players_total;

        if ($user) {
            $hasPlayerGame = PlayerGame::whereUserId($user->id)
                ->whereGameId($game->id)
                ->exists();

            if (!$hasPlayerGame) {
                $gamePlayerCount++;
            }
        }

        return max(1, $gamePlayerCount);
    }
}
