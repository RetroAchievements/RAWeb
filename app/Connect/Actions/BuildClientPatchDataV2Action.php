<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Enums\GameHashCompatibility;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BuildClientPatchDataV2Action
{
    public function execute(
        ?GameHash $gameHash = null,
        ?Game $game = null,
        ?User $user = null,
        ?AchievementFlag $flag = null,
    ): array {
        if (!$gameHash && !$game) {
            throw new InvalidArgumentException('Either gameHash or game must be provided to build patch data.');
        }

        if (!$gameHash) {
            return $this->buildPatchData($game, null, $user, $flag);
        }

        if (
            $gameHash->compatibility !== GameHashCompatibility::Compatible
            && $gameHash->compatibility_tester_id !== $user?->id
        ) {
            return $this->buildIncompatiblePatchData($game ?? $gameHash->game, $gameHash->compatibility, $user);
        }

        $actualLoadedGame = (new ResolveRootGameFromGameAndGameHashAction())->execute($gameHash, $game, $user);

        // If multiset is disabled or there's no user, just use the game directly.
        if (!$user || $user->is_globally_opted_out_of_subsets) {
            return $this->buildPatchData($actualLoadedGame, null, $user, $flag);
        }

        // Resolve sets once - we'll use this for building the full patch data.
        $resolvedSets = (new ResolveAchievementSetsAction())->execute($gameHash, $user);
        if ($resolvedSets->isEmpty()) {
            return $this->buildPatchData($actualLoadedGame, null, $user, $flag);
        }

        // Get the core game from the first resolved set.
        $derivedCoreSet = $resolvedSets->first();
        $derivedCoreGame = Game::find($derivedCoreSet->game_id) ?? $actualLoadedGame;

        // What type of set is the user loading? "core", "bonus", "specialty", or "exclusive".
        $loadedSetType = $this->determineLoadedHashSetType($gameHash, $resolvedSets);

        [$richPresenceGameId, $richPresencePatch] = $this->buildRichPresenceData(
            $actualLoadedGame,
            $derivedCoreGame,
            $loadedSetType
        );

        return $this->buildPatchData(
            $derivedCoreGame,
            $resolvedSets,
            $user,
            $flag,
            $richPresenceGameId,
            $richPresencePatch
        );
    }

    /**
     * @param Game $game The game to build root-level data for
     * @param Collection<int, GameAchievementSet>|null $resolvedSets The sets to send to the client/emulator
     * @param User|null $user The current user requesting the patch data (for player count calculations)
     * @param AchievementFlag|null $flag Optional flag to filter the achievements by (eg: only official achievements)
     * @param int|null $richPresenceGameId the game ID where the RP patch code comes from
     * @param string|null $richPresencePatch the RP patch code that the client should use
     */
    private function buildPatchData(
        Game $game,
        ?Collection $resolvedSets,
        ?User $user,
        ?AchievementFlag $flag,
        ?int $richPresenceGameId = null,
        ?string $richPresencePatch = null,
    ): array {
        $gamePlayerCount = $this->calculateGamePlayerCount($game, $user);

        $sets = [];
        if ($resolvedSets?->isNotEmpty()) {
            // Preload all games.
            $coreGameIds = $resolvedSets->pluck('core_game_id')->unique();
            $games = Game::whereIn('ID', $coreGameIds)->get()->keyBy('ID');

            foreach ($resolvedSets as $resolvedSet) {
                $setGame = $games[$resolvedSet->core_game_id];

                $achievements = $this->buildAchievementsData($resolvedSet, $gamePlayerCount, $flag);
                $leaderboards = $this->buildLeaderboardsData($setGame);

                $sets[] = [
                    'Title' => $resolvedSet->title,
                    'Type' => $resolvedSet->type->value,
                    'AchievementSetId' => $resolvedSet->achievementSet->id,
                    'GameId' => $resolvedSet->core_game_id,
                    'ImageIconUrl' => media_asset($setGame->ImageIcon),
                    'Achievements' => $achievements,
                    'Leaderboards' => $leaderboards,
                ];
            }
        } else {
            $coreAchievementSet = GameAchievementSet::where('game_id', $game->id)
                ->core()
                ->with('achievementSet.achievements.developer')
                ->first();

            $achievements = $this->buildAchievementsData($coreAchievementSet, $gamePlayerCount, $flag);
            $leaderboards = $this->buildLeaderboardsData($coreAchievementSet->game);

            $sets[] = [
                'Title' => $coreAchievementSet->title,
                'Type' => $coreAchievementSet->type->value,
                'AchievementSetId' => $coreAchievementSet->achievementSet->id,
                'GameId' => $coreAchievementSet->game_id,
                'ImageIconUrl' => media_asset($coreAchievementSet->game->ImageIcon),
                'Achievements' => $achievements,
                'Leaderboards' => $leaderboards,
            ];
        }

        return [
            'Success' => true,
            'GameId' => $game->id,
            'Title' => $game->title,
            'ImageIconUrl' => media_asset($game->ImageIcon),
            'RichPresenceGameId' => $richPresenceGameId ?? $game->id,
            'RichPresencePatch' => $richPresencePatch ?? $game->RichPresencePatch,
            'ConsoleId' => $game->system->id,
            'Sets' => $sets,
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
     * If the user loads a core set hash, always send the core game's RP.
     * If the user loads a bonus set hash, always send the core game's RP.
     * If the user loads a specialty set hash, always send the specialty game's RP if available.
     *   Fall back to the core game's RP if there's no specialty game RP.
     * If the user loads an exclusive set hash, always send the exclusive game's RP if available.
     *   Fall back to the core game's RP if there's no exclusive game RP.
     */
    private function buildRichPresenceData(Game $actualLoadedGame, Game $derivedCoreGame, AchievementSetType $loadedSetType): array
    {
        $doesLoadedGameHaveRp =
            !empty($actualLoadedGame->RichPresencePatch)
            && !is_null($actualLoadedGame->RichPresencePatch);

        $didUserLoadSpecialtyOrExclusive =
            $loadedSetType === AchievementSetType::Specialty
            || $loadedSetType === AchievementSetType::Exclusive;

        if ($doesLoadedGameHaveRp && $didUserLoadSpecialtyOrExclusive) {
            return [$actualLoadedGame->id, $actualLoadedGame->RichPresencePatch];
        }

        return [$derivedCoreGame->id, $derivedCoreGame->RichPresencePatch];
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

    private function buildIncompatiblePatchData(
        Game $game,
        GameHashCompatibility $gameHashCompatibility,
        ?User $user,
    ): array {
        $seeSupportedGameFiles = 'See the Supported Game Files page for this game to find a compatible version.';

        return [
            'Success' => true,
            'GameId' => VirtualGameIdService::encodeVirtualGameId($game->id, $gameHashCompatibility),
            'Title' => 'Unsupported Game Version',
            'ImageIconUrl' => media_asset($game->ImageIcon),
            'ConsoleId' => $game->ConsoleID,
            'Sets' => [
                'Title' => null,
                'Type' => AchievementSetType::Core->value,
                'AchievementSetId' => 0,
                'GameId' => VirtualGameIdService::encodeVirtualGameId($game->id, $gameHashCompatibility),
                'ImageIconUrl' => media_asset($game->ImageIcon),
                'Achievements' => [
                    (new CreateWarningAchievementAction())->execute(
                        title: 'Unsupported Game Version',
                        description: match ($gameHashCompatibility) {
                            GameHashCompatibility::Incompatible => "This version of the game is known to not work with the defined achievements. $seeSupportedGameFiles",
                            GameHashCompatibility::Untested => "This version of the game has not been tested to see if it works with the defined achievements. $seeSupportedGameFiles",
                            GameHashCompatibility::PatchRequired => "This version of the game requires a patch to support achievements. $seeSupportedGameFiles",
                            default => $seeSupportedGameFiles,
                        }),
                ],
                'Leaderboards' => [],
            ],
        ];
    }

    /**
     * @param Collection<int, GameAchievementSet> $resolvedAchievementSets
     */
    private function determineLoadedHashSetType(
        GameHash $gameHash,
        Collection $resolvedAchievementSets
    ): AchievementSetType {
        $loadedHashId = $gameHash->game->id;
        $loadedAchievementSetId = null;
        $setType = null;

        // First get the achievement set ID for the loaded hash.
        $loadedHashSets = GameAchievementSet::where('game_id', $loadedHashId)->get();
        if ($loadedHashSets->isNotEmpty()) {
            // Get the achievement set ID from the hash's game.
            $loadedAchievementSetId = $loadedHashSets->first()->achievement_set_id;

            // Now, find how this set is used in the resolved sets.
            foreach ($resolvedAchievementSets as $set) {
                if ($set->achievement_set_id === $loadedAchievementSetId) {
                    $setType = $set->type;
                    break;
                }
            }
        }

        return $setType;
    }
}
