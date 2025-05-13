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
use App\Platform\Services\VirtualGameIdService;
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
            return $this->buildPatchData($game, $user, $flag);
        }

        // If the hash is not marked as compatible, and the current user is not flagged to
        // be testing the hash, return a dummy set that will inform the user.
        if (
            $gameHash->compatibility !== GameHashCompatibility::Compatible
            && $gameHash->compatibility_tester_id !== $user?->id
        ) {
            return $this->buildIncompatiblePatchData($game ?? $gameHash->game, $gameHash->compatibility);
        }

        $rootGameId = (new ResolveRootGameIdFromGameAndGameHashAction())->execute($gameHash, $game, $user);
        $rootGame = Game::find($rootGameId);

        // This endpoint assumes multiset is not in use. Just use the game directly.
        return $this->buildPatchData($rootGame, $user, $flag);
    }

    /**
     * @param Game $game The game to build root-level data for
     * @param User|null $user The current user requesting the patch data (for player count calculations)
     * @param AchievementFlag|null $flag Optional flag to filter the achievements by (eg: only official achievements)
     */
    private function buildPatchData(
        Game $game,
        ?User $user,
        ?AchievementFlag $flag,
    ): array {
        $gamePlayerCount = $this->calculateGamePlayerCount($game, $user);

        $coreAchievementSet = GameAchievementSet::where('game_id', $game->id)
            ->core()
            ->with('achievementSet.achievements.developer')
            ->first();

        return [
            'Success' => true,
            'PatchData' => [
                ...$this->buildBaseGameData($game),
                'Achievements' => $coreAchievementSet
                    ? $this->buildAchievementsData($coreAchievementSet, $gamePlayerCount, $flag)
                    : [],
                'Leaderboards' => $this->buildLeaderboardsData($game),
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

    /**
     * @param Game $game The game to build root-level data for
     */
    private function buildIncompatiblePatchData(
        Game $game,
        GameHashCompatibility $gameHashCompatibility,
    ): array {
        $seeSupportedGameFiles = 'See the Supported Game Files page for this game to find a compatible version.';

        return [
            'Success' => true,
            'PatchData' => [
                'ID' => VirtualGameIdService::encodeVirtualGameId($game->id, $gameHashCompatibility),
                'Title' => 'Unsupported Game Version',
                'ConsoleID' => $game->ConsoleID,
                'ImageIcon' => $game->ImageIcon,
                'ImageIconURL' => media_asset($game->ImageIcon),
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
}
