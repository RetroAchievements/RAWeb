<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class BuildClientPatchDataAction
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
     * @throws InvalidArgumentException when neither $gameHash nor $game is provided
     */
    public function execute(
        ?GameHash $gameHash = null,
        ?Game $game = null,
        ?User $user = null,
        ?AchievementFlag $flag = null,
    ): array {
        if (!$gameHash && !$game) {
            throw new InvalidArgumentException('Either gameHash or game must be provided to build patch data.');
        }

        // For legacy clients that don't provide a hash, just use the game directly.
        if (!$gameHash) {
            return $this->buildPatchData($game, null, $user, $flag);
        }

        $rootGameId = (new ResolveRootGameIdFromGameAndGameHashAction())->execute($gameHash, $game, $user);
        $rootGame = Game::find($rootGameId);

        // If multiset is disabled or there's no user, just use the game directly.
        if (!$user || $user->is_globally_opted_out_of_subsets) {
            return $this->buildPatchData($rootGame, null, $user, $flag);
        }

        // Resolve sets once - we'll use this for building the full patch data.
        $resolvedSets = (new ResolveAchievementSetsAction())->execute($gameHash, $user);
        if ($resolvedSets->isEmpty()) {
            return $this->buildPatchData($rootGame, null, $user, $flag);
        }

        // Get the core game from the first resolved set.
        $coreSet = $resolvedSets->first();
        $coreGame = Game::find($coreSet->game_id) ?? $rootGame;

        $richPresencePatch = $coreGame->RichPresencePatch;

        // For specialty/exclusive sets, we use:
        // - The root game's ID and achievements (already determined by ResolveRootGameIdFromGameAndGameHashAction).
        // - The core game's title and image.
        // - The root game's RP if present, otherwise fall back to core game's RP.
        if ($rootGameId === $gameHash->game->id) {
            $richPresencePatch = $gameHash->game->RichPresencePatch ?: $richPresencePatch;

            return $this->buildPatchData($rootGame, $resolvedSets, $user, $flag, $richPresencePatch, $coreGame);
        }

        // For all other cases (including bonus sets), we use the core game's data.
        return $this->buildPatchData($coreGame, $resolvedSets, $user, $flag, $richPresencePatch);
    }

    /**
     * @param Game $game The game to build root-level data for
     * @param Collection<int, GameAchievementSet>|null $resolvedSets The sets to send to the client/emulator
     * @param User|null $user The current user requesting the patch data (for player count calculations)
     * @param AchievementFlag|null $flag Optional flag to filter the achievements by (eg: only official achievements)
     * @param string|null $richPresencePatch The RP patch code that the client should use
     * @param Game|null $titleGame Optional game to use for title and image (for specialty/exclusive sets)
     */
    private function buildPatchData(
        Game $game,
        ?Collection $resolvedSets,
        ?User $user,
        ?AchievementFlag $flag,
        ?string $richPresencePatch = null,
        ?Game $titleGame = null
    ): array {
        $gamePlayerCount = $this->calculateGamePlayerCount($game, $user);

        $coreAchievementSet = GameAchievementSet::where('game_id', $game->id)
            ->core()
            ->with('achievementSet.achievements.developer')
            ->first();

        // Don't fetch the games in the loop if we have sets. Grab them all in a single query.
        $sets = [];
        if ($resolvedSets?->isNotEmpty()) {
            $coreGameIds = $resolvedSets->pluck('core_game_id')->unique();
            $achievementSetIds = $resolvedSets->pluck('achievement_set_id')->unique();

            // Preload all games.
            $games = Game::whereIn('ID', $coreGameIds)->get()->keyBy('ID');

            // Preload all GameAchievementSet entities we'll need.
            $gameAchievementSets = GameAchievementSet::where(function ($query) use ($game, $achievementSetIds) {
                $query->where('game_id', $game->id)->whereIn('achievement_set_id', $achievementSetIds);
            })->orWhere(function ($query) use ($resolvedSets) {
                $query
                    ->whereIn('game_id', $resolvedSets->pluck('game_id'))
                    ->whereIn('achievement_set_id', $resolvedSets->pluck('achievement_set_id'));
            })->get();

            foreach ($resolvedSets as $resolvedSet) {
                // We don't want to include sets in the list that are duplicative
                // with the root-level data in the response (for achievements & leaderboards).
                if ($resolvedSet->game_id === $game->id && $resolvedSet->type === AchievementSetType::Core) {
                    continue;
                }

                // For specialty/exclusive sets, instead of looking up how this game's
                // achievement set is attached, look up how the resolved set's achievement
                // set is attached to its parent.
                $setAttachment = $gameAchievementSets->first(function ($attachment) use ($resolvedSet) {
                    return
                        $attachment->game_id === $resolvedSet->game_id
                        && $attachment->achievement_set_id === $resolvedSet->achievement_set_id
                    ;
                });

                // Get the achievement set for the current game.
                $gameAchievementSet = $gameAchievementSets->first(function ($attachment) use ($game, $resolvedSet) {
                    return
                        $attachment->game_id === $game->id
                        && $attachment->achievement_set_id === $resolvedSet->achievement_set_id
                    ;
                });

                // Skip if this is a specialty/exclusive set that we're directly loading a hash for.
                if (
                    $setAttachment
                    && in_array($setAttachment->type, [AchievementSetType::Specialty, AchievementSetType::Exclusive])
                    && $gameAchievementSet !== null
                ) {
                    continue;
                }

                // Get the achievements for this set. If there are no published
                // achievements, we won't bother sending the set to the client.
                $achievements = $this->buildAchievementsData($resolvedSet, $gamePlayerCount, $flag);
                if (empty($achievements)) {
                    continue;
                }

                $setGame = $games[$resolvedSet->core_game_id];
                $sets[] = [
                    'GameID' => $setGame->id,
                    'GameAchievementSetID' => $resolvedSet->id,
                    'SetTitle' => $resolvedSet->title,
                    'Type' => $resolvedSet->type->value,
                    'ImageIcon' => $setGame->ImageIcon,
                    'ImageIconURL' => media_asset($setGame->ImageIcon),
                    'Achievements' => $achievements,
                    'Leaderboards' => $this->buildLeaderboardsData($setGame),
                ];
            }
        }

        return [
            'Success' => true,
            'PatchData' => [
                ...$this->buildBaseGameData($game, $richPresencePatch, $titleGame),
                'Achievements' => $coreAchievementSet
                    ? $this->buildAchievementsData($coreAchievementSet, $gamePlayerCount, $flag)
                    : [],
                'Leaderboards' => $this->buildLeaderboardsData($game),
                ...(!empty($sets) ? ['Sets' => $sets] : []),
            ],
        ];
    }

    /**
     * Builds achievement information needed by emulators.
     *
     * @param GameAchievementSet $gameAchievementSet The achievement set to build achievement data for
     * @param int $gamePlayerCount The total number of players (minimum of 1 to prevent division by zero)
     * @param AchievementFlag|null $flag Optional flag to filter the achievements by (eg: only official achievements)
     */
    private function buildAchievementsData(
        GameAchievementSet $gameAchievementSet,
        int $gamePlayerCount,
        ?AchievementFlag $flag,
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
     * Builds the basic game information needed by emulators.
     */
    private function buildBaseGameData(
        Game $game,
        ?string $richPresencePatch,
        ?Game $titleGame,
    ): array {
        // If a title game is provided, use its title and image.
        $titleGame = $titleGame ?? $game;

        return [
            'ID' => $game->id,
            'ParentID' => $titleGame->id,
            'Title' => $titleGame->title,
            'ImageIcon' => $titleGame->ImageIcon,
            'RichPresencePatch' => $richPresencePatch ?? $game->RichPresencePatch,
            'ConsoleID' => $game->ConsoleID,
            'ImageIconURL' => media_asset($titleGame->ImageIcon),
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
