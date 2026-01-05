<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Enums\ClientSupportLevel;
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
use App\Platform\Services\UserAgentService;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GetAchievementSetsAction extends BaseAuthenticatedApiAction
{
    protected int $gameId;
    protected ?string $gameHashMd5;
    protected ?bool $isPromoted;
    protected ClientSupportLevel $clientSupportLevel;

    public function execute(User $user, int $gameId = 0, ?string $gameHash = null, ?bool $isPromoted = true): array
    {
        $this->user = $this->user;
        $this->gameId = $gameId;
        $this->gameHashMd5 = $gameHash;
        $this->isPromoted = $isPromoted;
        $this->clientSupportLevel = ClientSupportLevel::Full;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g']) && !$request->has(['m'])) {
            return $this->missingParameters();
        }

        $this->gameId = request()->integer('g', 0);
        $this->gameHashMd5 = request()->input('m');

        $flag = request()->integer('f', 0);
        $this->isPromoted = Achievement::isPromotedFromLegacyFlags($flag);

        $this->userAgentService = new UserAgentService();
        $this->clientSupportLevel = $this->userAgentService->getSupportLevel(request()->header('User-Agent'));
        if ($this->clientSupportLevel === ClientSupportLevel::Blocked) {
            return $this->unsupportedClient();
        }

        return null;
    }

    protected function process(): array
    {
        $game = null;
        $gameHash = null;

        if (VirtualGameIdService::isVirtualGameId($this->gameId)) {
            // we don't have a specific game hash. check to see if the user is selected for
            // compatibility testing for any hash for the game. if so, load it.
            [$realGameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($this->gameId);
            $gameHash = GameHash::where('game_id', $realGameId)->where('compatibility_tester_id', $this->user->id)->first();
            $gameHash ??= VirtualGameIdService::makeVirtualGameHash($this->gameId);
        } elseif ($this->gameHashMd5) {
            $gameHash = GameHash::whereMd5($this->gameHashMd5)->first();
        } else {
            $game = Game::find($this->gameId);
        }

        if ($gameHash) {
            $game ??= $gameHash->game;
            $response = $this->buildMultisetPatchData($game, $gameHash);
        } elseif ($game) {
            // if no hash was provided, this is a legacy call and only the specific game data should be returned.
            $response = $this->buildPatchData($game, null);
        } else {
            return $this->gameNotFound();
        }

        if ($this->clientSupportLevel !== ClientSupportLevel::Full && $game->achievements_published > 0) {
            $this->injectClientSupportWarning($response);
        }

        return $response;
    }

    private function buildMultisetPatchData(Game $game, GameHash $gameHash): array
    {
        // If the hash is not marked as compatible, and the current user is not flagged to
        // be testing the hash, a QA member, or the set author, return a dummy set that will
        // inform the user.
        if ($gameHash->compatibility !== GameHashCompatibility::Compatible) {
            $canSeeIncompatibleSet = $this->user->can('loadIncompatibleSet', $gameHash);
            if (!$canSeeIncompatibleSet) {
                return $this->buildIncompatiblePatchData($game, $gameHash->compatibility);
            }
        }

        $actualLoadedGame = (new ResolveRootGameFromGameAndGameHashAction())->execute($gameHash, $game, $this->user);

        // Resolve sets once - we'll use this for building the full patch data.
        $resolvedSets = (new ResolveAchievementSetsAction())->execute($gameHash, $this->user);
        if ($resolvedSets->isEmpty()) {
            /**
             * Check if the game actually has achievement sets. If not, return an empty Sets
             * list (not a warning due to manual opt out). Only show a warning if the user
             * has opted out of existing sets.
             */
            $doesGameHaveAnySets = GameAchievementSet::where('game_id', $actualLoadedGame->id)->exists();
            if (!$doesGameHaveAnySets) {
                return $this->buildPatchData($actualLoadedGame, null, compatibility: $gameHash->compatibility);
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
        $hasLocalPreferences = $this->getDoesUserHaveLocalPreferences($derivedCoreSet->game_id);
        if ($this->user->is_globally_opted_out_of_subsets && !$hasLocalPreferences) {
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
            $richPresenceGameId,
            $richPresencePatch,
            $gameHash->compatibility,
        );
    }

    /**
     * @param Game $game The game to build root-level data for
     * @param Collection<int, GameAchievementSet>|null $resolvedSets The sets to send to the client/emulator
     * @param int|null $richPresenceGameId the game ID where the RP patch code comes from
     * @param string|null $richPresencePatch the RP patch code that the client should use
     * @param GameHashCompatibility $compatibility Indicates the compatibility of the hash being loaded (affects game title)
     */
    private function buildPatchData(
        Game $game,
        ?Collection $resolvedSets,
        ?int $richPresenceGameId = null,
        ?string $richPresencePatch = null,
        GameHashCompatibility $compatibility = GameHashCompatibility::Compatible,
    ): array {
        $gamePlayerCount = $this->calculateGamePlayerCount($game);

        $sets = [];
        if ($resolvedSets?->isNotEmpty()) {
            // Preload all games.
            $coreGameIds = $resolvedSets->pluck('core_game_id')->unique();
            $games = Game::whereIn('id', $coreGameIds)->get()->keyBy('id');

            foreach ($resolvedSets as $resolvedSet) {
                $setGame = $games[$resolvedSet->core_game_id];

                $achievements = $this->buildAchievementsData($resolvedSet, $gamePlayerCount);
                $leaderboards = $this->buildLeaderboardsData($setGame);

                $sets[] = [
                    'Title' => $resolvedSet->title,
                    'Type' => $resolvedSet->type->value,
                    'AchievementSetId' => $resolvedSet->achievementSet->id,
                    'GameId' => $resolvedSet->core_game_id,
                    'ImageIconUrl' => media_asset($setGame->image_icon_asset_path),
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
                ? $this->buildAchievementsData($coreAchievementSet, $gamePlayerCount)
                : [];
            $leaderboards = $coreAchievementSet
                ? $this->buildLeaderboardsData($coreAchievementSet->game)
                : [];

            $sets[] = [
                'Title' => $coreAchievementSet?->title ?? $game->title,
                'Type' => $coreAchievementSet?->type->value ?? AchievementSetType::Core->value,
                'AchievementSetId' => $coreAchievementSet?->achievementSet->id ?? 0,
                'GameId' => $coreAchievementSet?->game_id ?? $game->id,
                'ImageIconUrl' => media_asset($coreAchievementSet?->game->image_icon_asset_path ?? $game->image_icon_asset_path),
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
            'ImageIconUrl' => media_asset($game->image_icon_asset_path),
            'RichPresenceGameId' => $richPresenceGameId ?? $game->id,
            'RichPresencePatch' => $richPresencePatch ?? $game->trigger_definition,
            'ConsoleId' => $game->system->id,
            'Sets' => $sets,
        ];
    }

    /**
     * Builds achievement information needed by emulators.
     *
     * @param GameAchievementSet $gameAchievementSet The achievement set to build achievement data for
     * @param int $gamePlayerCount The total number of players (minimum of 1 to prevent division by zero)
     */
    private function buildAchievementsData(GameAchievementSet $gameAchievementSet, int $gamePlayerCount): array
    {
        /** @var Collection<int, Achievement> $achievements */
        $achievements = $gameAchievementSet->achievementSet
            ->achievements()
            ->with('developer')
            ->orderBy('order_column') // explicit display order
            ->orderBy('id')           // tiebreaker on creation sequence
            ->get();

        if ($this->isPromoted !== null) {
            $achievements = $achievements->where('is_promoted', '=', $this->isPromoted);
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
            ->orderBy('order_column') // explicit display order
            ->orderBy('id')           // tiebreaker on creation sequence
            ->get();

        foreach ($leaderboards as $leaderboard) {
            $leaderboardsData[] = [
                'ID' => $leaderboard->id,
                'Mem' => $leaderboard->trigger_definition,
                'Format' => $leaderboard->format,
                'LowerIsBetter' => $leaderboard->rank_asc,
                'Title' => $leaderboard->title,
                'Description' => $leaderboard->description,
                'Hidden' => ($leaderboard->order_column < 0),
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
            !empty($actualLoadedGame->trigger_definition)
            && !is_null($actualLoadedGame->trigger_definition);

        $didUserLoadSpecialtyOrExclusive =
            $loadedSetType === AchievementSetType::Specialty
            || $loadedSetType === AchievementSetType::Exclusive;

        if ($doesLoadedGameHaveRp && $didUserLoadSpecialtyOrExclusive) {
            return [$actualLoadedGame->id, $actualLoadedGame->trigger_definition];
        }

        return [$derivedCoreGame->id, $derivedCoreGame->trigger_definition];
    }

    /**
     * Calculates the total number of players for the game, which ultimately gets used in
     * achievement rarity calculations.
     *
     * This method adds 1 to the total if the requesting user hasn't played the game yet,
     * which ensures accurate rarity predictions for when they unlock achievements.
     *
     * @param Game $game The game to calculate player count for
     *
     * @return int The total number of players (minimum of 1 to prevent division by zero)
     */
    private function calculateGamePlayerCount(Game $game): int
    {
        $gamePlayerCount = $game->players_total;

        $hasPlayerGame = PlayerGame::whereUserId($this->user->id)
            ->whereGameId($game->id)
            ->exists();

        if (!$hasPlayerGame) {
            $gamePlayerCount++;
        }

        return max(1, $gamePlayerCount);
    }

    private function buildIncompatiblePatchData(
        Game $game,
        GameHashCompatibility $gameHashCompatibility,
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
            'ImageIconUrl' => media_asset($game->image_icon_asset_path),
            'ConsoleId' => $game->system_id,
            'Sets' => [
                [
                    'Title' => null,
                    'Type' => AchievementSetType::Core->value,
                    'AchievementSetId' => $achievementSetId,
                    'GameId' => VirtualGameIdService::encodeVirtualGameId($game->id, $gameHashCompatibility),
                    'ImageIconUrl' => media_asset($game->image_icon_asset_path),
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
            'ImageIconUrl' => media_asset($game->image_icon_asset_path),
            'RichPresenceGameId' => $game->id,
            'RichPresencePatch' => $game->trigger_definition,
            'ConsoleId' => $game->system->id,
            'Sets' => [
                [
                    'Title' => $coreAchievementSet?->title ?? $game->title,
                    'Type' => AchievementSetType::Core->value,
                    'AchievementSetId' => $achievementSetId,
                    'GameId' => $game->id,
                    'ImageIconUrl' => media_asset($game->image_icon_asset_path),
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
     * This warning achievement should appear at the top of the emulator's achievements
     * list. It should automatically unlock after a few seconds of patch data retrieval.
     * The intention is to notify a user that they are using an outdated client
     * and need to update, as well as what the repercussions of their continued
     * play session with their current client might be.
     */
    private function injectClientSupportWarning(array &$response): void
    {
        if (!empty($response['Sets'])) {
            $warningAchievement = (new CreateWarningAchievementAction())->execute(
                title: match ($this->clientSupportLevel) {
                    ClientSupportLevel::Outdated => 'Warning: Outdated Emulator (please update)',
                    ClientSupportLevel::Unsupported => 'Warning: Unsupported Emulator',
                    default => 'Warning: Unknown Emulator',
                },
                description: ($this->clientSupportLevel === ClientSupportLevel::Outdated) ?
                    'Hardcore unlocks cannot be earned using this version of this emulator.' :
                    'Hardcore unlocks cannot be earned using this emulator.'
            );

            // For the V2 format, if there are sets, add the warning to the first set.
            $response['Sets'][0]['Achievements'] = [
                $warningAchievement,
                ...$response['Sets'][0]['Achievements'] ?? [],
            ];
        }

        if ($this->clientSupportLevel === ClientSupportLevel::Unknown) {
            $response['Warning'] = 'The server does not recognize this client and will not allow hardcore unlocks. Please send a message to RAdmin on the RetroAchievements website for information on how to submit your emulator for hardcore consideration.';
        }
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
    private function getDoesUserHaveLocalPreferences(int $gameId): bool
    {
        $gameAchievementSetIds = GameAchievementSet::where('game_id', $gameId)->pluck('id');

        return UserGameAchievementSetPreference::where('user_id', $this->user->id)
            ->whereIn('game_achievement_set_id', $gameAchievementSetIds)
            ->exists();
    }
}
