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
use App\Models\UserGameAchievementSetPreference;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BuildClientPatchDataV2Action
{
    public function execute(
        ?GameHash $gameHash = null,
        ?Game $game = null,
        ?User $user = null,
        ?bool $isPromoted = null,
    ): array {
        if (!$gameHash && !$game) {
            throw new InvalidArgumentException('Either gameHash or game must be provided to build achievementsets data.');
        }

        if (!$gameHash) {
            return $this->buildPatchData($game, null, $user, $isPromoted);
        }

        // If the hash is not marked as compatible, and the current user is not flagged to
        // be testing the hash, a QA member, or the set author, return a dummy set that will
        // inform the user.
        if ($gameHash->compatibility !== GameHashCompatibility::Compatible) {
            $game ??= $gameHash->game;

            $canSeeIncompatibleSet = $user && $user->can('loadIncompatibleSet', $gameHash);
            if (!$canSeeIncompatibleSet) {
                return $this->buildIncompatiblePatchData($game, $gameHash->compatibility, $user);
            }
        }

        $actualLoadedGame = (new ResolveRootGameFromGameAndGameHashAction())->execute($gameHash, $game, $user);

        // If there's no user, just use the game directly.
        if (!$user) {
            return $this->buildPatchData($actualLoadedGame, null, $user, $isPromoted, compatibility: $gameHash->compatibility);
        }

        // Resolve sets once - we'll use this for building the full patch data.
        $resolvedSets = (new ResolveAchievementSetsAction())->execute($gameHash, $user);
        if ($resolvedSets->isEmpty()) {
            /**
             * Check if the game actually has achievement sets. If not, return an empty Sets
             * list (not a warning due to manual opt out). Only show a warning if the user
             * has opted out of existing sets.
             */
            $doesGameHaveAnySets = GameAchievementSet::where('game_id', $actualLoadedGame->id)->exists();
            if (!$doesGameHaveAnySets) {
                return $this->buildPatchData($actualLoadedGame, null, $user, $isPromoted, compatibility: $gameHash->compatibility);
            }

            return $this->buildAllSetsOptedOutPatchData($actualLoadedGame, $gameHash->compatibility);
        }

        // Get the core game from the first resolved set.
        $derivedCoreSet = $resolvedSets->first();

        /**
         * When the user is globally opted out and has no local preferences, use legacy
         * behavior (treat the loaded game/hash as standalone, like pre-multiset).
         *
         * When the user has local preferences, use multiset behavior and derive the
         * root game from resolved sets.
         */
        $hasLocalPreferences = $this->getDoesUserHaveLocalPreferences($user, $derivedCoreSet->game_id);
        if ($user->is_globally_opted_out_of_subsets && !$hasLocalPreferences) {
            $derivedCoreGame = $actualLoadedGame;
        } else {
            $derivedCoreGame = Game::find($derivedCoreSet->game_id) ?? $actualLoadedGame;
        }

        // What type of set is the user loading? "core", "bonus", "specialty", or "exclusive".
        $loadedSetType = $this->determineLoadedHashSetType($gameHash, $derivedCoreGame->gameAchievementSets);

        [$richPresenceGameId, $richPresencePatch] = $this->buildRichPresenceData(
            $actualLoadedGame,
            $derivedCoreGame,
            $loadedSetType
        );

        return $this->buildPatchData(
            $derivedCoreGame,
            $resolvedSets,
            $user,
            $isPromoted,
            $richPresenceGameId,
            $richPresencePatch,
            $gameHash->compatibility,
        );
    }

    /**
     * @param Game $game The game to build root-level data for
     * @param Collection<int, GameAchievementSet>|null $resolvedSets The sets to send to the client/emulator
     * @param User|null $user The current user requesting the patch data (for player count calculations)
     * @param bool|null $isPromoted Optional flag to filter the assets by (eg: only published assets)
     * @param int|null $richPresenceGameId the game ID where the RP patch code comes from
     * @param string|null $richPresencePatch the RP patch code that the client should use
     * @param GameHashCompatibility $compatibility Indicates the compatibility of the hash being loaded (affects game title)
     */
    private function buildPatchData(
        Game $game,
        ?Collection $resolvedSets,
        ?User $user,
        ?bool $isPromoted,
        ?int $richPresenceGameId = null,
        ?string $richPresencePatch = null,
        GameHashCompatibility $compatibility = GameHashCompatibility::Compatible,
    ): array {
        $gamePlayerCount = $this->calculateGamePlayerCount($game, $user);

        $sets = [];
        if ($resolvedSets?->isNotEmpty()) {
            // Preload all games.
            $coreGameIds = $resolvedSets->pluck('core_game_id')->unique();
            $games = Game::whereIn('ID', $coreGameIds)->get()->keyBy('ID');

            foreach ($resolvedSets as $resolvedSet) {
                $setGame = $games[$resolvedSet->core_game_id];

                $achievements = $this->buildAchievementsData($resolvedSet, $gamePlayerCount, $isPromoted);
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

            $achievements = $coreAchievementSet
                ? $this->buildAchievementsData($coreAchievementSet, $gamePlayerCount, $isPromoted)
                : [];
            $leaderboards = $coreAchievementSet
                ? $this->buildLeaderboardsData($coreAchievementSet->game)
                : [];

            $sets[] = [
                'Title' => $coreAchievementSet?->title ?? $game->title,
                'Type' => $coreAchievementSet?->type->value ?? AchievementSetType::Core->value,
                'AchievementSetId' => $coreAchievementSet?->achievementSet->id ?? 0,
                'GameId' => $coreAchievementSet?->game_id ?? $game->id,
                'ImageIconUrl' => media_asset($coreAchievementSet?->game->ImageIcon ?? $game->ImageIcon),
                'Achievements' => $achievements,
                'Leaderboards' => $leaderboards,
            ];
        }

        $title = ($compatibility === GameHashCompatibility::Compatible)
            ? $game->title
            : "Unsupported Game Version ($game->title)";

        return [
            'Success' => true,
            'GameId' => $game->id,
            'Title' => $title,
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
     * @param bool|null $isPromoted Optional flag to filter the assets by (eg: only published assets)
     */
    private function buildAchievementsData(
        GameAchievementSet $gameAchievementSet,
        int $gamePlayerCount,
        ?bool $isPromoted,
    ): array {
        /** @var Collection<int, Achievement> $achievements */
        $achievements = $gameAchievementSet->achievementSet
            ->achievements()
            ->with('developer')
            ->orderBy('order_column') // explicit display order
            ->orderBy('id')           // tiebreaker on creation sequence
            ->get();

        if ($isPromoted !== null) {
            $achievements = $achievements->where('is_promoted', '=', $isPromoted);
        }

        $achievementsData = [];
        foreach ($achievements as $achievement) {
            // Calculate rarity assuming it will be used when the player unlocks the achievement,
            // which implies they haven't already unlocked it.
            $rarity = min(100.0, round((float) ($achievement->unlocks_total + 1) * 100 / $gamePlayerCount, 2));
            $rarityHardcore = min(100.0, round((float) ($achievement->unlocks_hardcore + 1) * 100 / $gamePlayerCount, 2));

            $achievementsData[] = [
                'ID' => $achievement->id,
                'MemAddr' => $achievement->trigger_definition,
                'Title' => $achievement->title,
                'Description' => $achievement->description,
                'Points' => $achievement->points,
                'Author' => $achievement->developer->display_name ?? '',
                'Modified' => $achievement->modified_at->unix(),
                'Created' => $achievement->created_at->unix(),
                'BadgeName' => $achievement->image_name,
                'Flags' => $achievement->is_promoted ? Achievement::FLAG_PROMOTED : Achievement::FLAG_UNPROMOTED,
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
            ->where('state', LeaderboardState::Active) // only active leaderboards
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

        $coreAchievementSet = GameAchievementSet::where('game_id', $game->id)
            ->core()
            ->first();
        $achievementSetId = $coreAchievementSet ? $coreAchievementSet->achievement_set_id : 0;

        return [
            'Success' => true,
            'GameId' => VirtualGameIdService::encodeVirtualGameId($game->id, $gameHashCompatibility),
            'Title' => "Unsupported Game Version ($game->title)",
            'ImageIconUrl' => media_asset($game->ImageIcon),
            'ConsoleId' => $game->ConsoleID,
            'Sets' => [
                [
                    'Title' => null,
                    'Type' => AchievementSetType::Core->value,
                    'AchievementSetId' => $achievementSetId,
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
            ],
        ];
    }

    /**
     * Returns patch data with a warning achievement when the user has opted out of all sets.
     */
    private function buildAllSetsOptedOutPatchData(
        Game $game,
        GameHashCompatibility $compatibility,
    ): array {
        $coreAchievementSet = GameAchievementSet::where('game_id', $game->id)
            ->core()
            ->first();
        $achievementSetId = $coreAchievementSet ? $coreAchievementSet->achievement_set_id : 0;

        $title = ($compatibility === GameHashCompatibility::Compatible)
            ? $game->title
            : "Unsupported Game Version ({$game->title})";

        return [
            'Success' => true,
            'GameId' => $game->id,
            'Title' => $title,
            'ImageIconUrl' => media_asset($game->ImageIcon),
            'RichPresenceGameId' => $game->id,
            'RichPresencePatch' => $game->RichPresencePatch,
            'ConsoleId' => $game->system->id,
            'Sets' => [
                [
                    'Title' => $coreAchievementSet?->title ?? $game->title,
                    'Type' => AchievementSetType::Core->value,
                    'AchievementSetId' => $achievementSetId,
                    'GameId' => $game->id,
                    'ImageIconUrl' => media_asset($game->ImageIcon),
                    'Achievements' => [
                        (new CreateWarningAchievementAction())->execute(
                            title: 'All Sets Opted Out',
                            description: 'You have opted out of all achievement sets for this game. Visit the game page to change your preferences.',
                        ),
                    ],
                    'Leaderboards' => [],
                ],
            ],
        ];
    }

    /**
     * @param Collection<int, GameAchievementSet> $resolvedAchievementSets
     */
    private function determineLoadedHashSetType(
        GameHash $gameHash,
        Collection $resolvedAchievementSets,
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

    /**
     * Checks if the user has any local preferences for the given game's achievement sets.
     */
    private function getDoesUserHaveLocalPreferences(User $user, int $gameId): bool
    {
        $gameAchievementSetIds = GameAchievementSet::where('game_id', $gameId)->pluck('id');

        return UserGameAchievementSetPreference::where('user_id', $user->id)
            ->whereIn('game_achievement_set_id', $gameAchievementSetIds)
            ->exists();
    }
}
