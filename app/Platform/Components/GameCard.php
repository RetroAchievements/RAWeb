<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Community\Enums\ClaimStatus;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class GameCard extends Component
{
    private int $gameId;
    private int $hubConsoleId = 100;
    private int $eventConsoleId = 101;
    private ?array $userHighestGameAward = null;
    private ?User $userContext;

    public function __construct(int $gameId, ?string $targetUsername = null)
    {
        $this->gameId = $gameId;
        $this->userContext = User::firstWhere('User', $targetUsername) ?? Auth::user() ?? null;
    }

    public function render(): ?View
    {
        $rawGameData = $this->getGameData($this->gameId);

        if (!$rawGameData) {
            return null;
        }

        if ($this->userContext) {
            $this->userHighestGameAward = PlayerBadge::getHighestUserAwardForGameId(
                $this->userContext,
                $this->gameId,
            );
        }

        $cardViewValues = $this->buildAllCardViewValues(
            $rawGameData,
            $this->userHighestGameAward,
        );

        return view('components.cards.game', $cardViewValues);
    }

    /**
     * Retrieves the game data for a given game ID.
     * It first checks if the data is available in cache. If it is, the cached data is returned.
     * Otherwise, the method fetches the data from the database, caches it, and then returns it.
     *
     * The fetched game data includes game metadata, console info, core achievements, dev claims,
     * and alternate games (used if the game is for a hub).
     *
     * @return ?array<mixed> The game data. Returns null if no game is found with the given game ID.
     */
    private function getGameData(int $gameId): ?array
    {
        $cacheKey = CacheKey::buildGameCardDataCacheKey($gameId);

        if (Cache::store('array')->has($cacheKey)) {
            return Cache::store('array')->get($cacheKey);
        }

        $loadGameCardData = (function () use ($gameId): ?array {
            $foundGame = Game::with([
                'system',
                'achievements' => function ($query) {
                    $query->published();
                },
            ])->find($gameId);

            if (!$foundGame) {
                return null;
            }

            $foundGameConsoleId = $foundGame->system->ID;
            $foundGameAchievements = $foundGame->achievements->toArray();

            $foundClaims = AchievementSetClaim::where('game_id', $gameId)->get()->toArray();

            $foundAltGames = [];
            if ($foundGameConsoleId === $this->hubConsoleId) {
                $foundAltGames = GameAlternative::where('gameID', $gameId)->get()->toArray();
            }

            return array_merge(
                $foundGame->toArray(), [
                    'ConsoleID' => $foundGameConsoleId,
                    'ConsoleName' => $foundGame->system->Name,
                    'Achievements' => $foundGameAchievements,
                    'Claims' => $foundClaims,
                    'AltGames' => $foundAltGames,
                ]
            );
        })();

        Cache::store('array')->put($cacheKey, $loadGameCardData);

        return $loadGameCardData;
    }

    /**
     * Builds a human-readable label listing all active developers' usernames.
     * If no developers are active, it returns an empty string.
     * For one developer, it returns the developer's username.
     * For two developers, it joins the usernames like "AAA and BBB".
     * For more than two developers, it joins the usernames like "AAA, BBB, and CCC".
     *
     * @param array $activeDeveloperUsernames the list of usernames with active claims
     *
     * @return string a human-readable label listing all active developers' usernames
     */
    private function buildActiveDevelopersLabel(array $activeDeveloperUsernames): string
    {
        $usernameCount = count($activeDeveloperUsernames);

        if ($usernameCount === 0) {
            return '';
        }

        if ($usernameCount === 1) {
            return $activeDeveloperUsernames[0];
        }

        if ($usernameCount === 2) {
            return implode(' and ', $activeDeveloperUsernames);
        }

        $lastDeveloper = array_pop($activeDeveloperUsernames);

        return implode(', ', $activeDeveloperUsernames) . ', and ' . $lastDeveloper;
    }

    /**
     * Builds an array containing all data required for a game card view.
     * It uses various other private methods to build specific parts of the data.
     *
     * @param ?array $userHighestGameAward an array containing `highestAwardKind` and `highestAward`, see PlayerBadge::getHighestUserAwardForGameId()
     */
    private function buildAllCardViewValues(array $rawGameData, ?array $userHighestGameAward): array
    {
        $rawTitle = $rawGameData['Title'];
        $badgeUrl = media_asset($rawGameData['ImageIcon']);
        $gameSystemIconSrc = getSystemIconUrl($rawGameData['ConsoleID']);
        $consoleName = $rawGameData['ConsoleName'];
        $achievementsCount = count($rawGameData['Achievements']);
        $isHub = $rawGameData['ConsoleID'] === $this->hubConsoleId;
        $isEvent = $rawGameData['ConsoleID'] === $this->eventConsoleId;
        $altGamesCount = count($rawGameData['AltGames']);

        [$pointsSum, $retroPointsSum, $retroRatio, $lastUpdated] = $this->buildCardAchievementsData(
            $rawGameData['Achievements'],
            $rawGameData['Updated'],
        );

        [$highestProgressionStatus, $highestProgressionAwardDate] = $this->buildCardUserProgressionData(
            $userHighestGameAward,
            $isEvent,
        );

        $activeClaims = array_filter($rawGameData['Claims'], fn ($claim) => ClaimStatus::isActive($claim['Status']));
        $activeDeveloperUsernames = array_map(fn ($activeClaim) => $activeClaim['User'], array_values($activeClaims));
        $activeDevelopersLabel = $this->buildActiveDevelopersLabel($activeDeveloperUsernames);

        return compact(
            'achievementsCount',
            'activeDevelopersLabel',
            'activeDeveloperUsernames',
            'altGamesCount',
            'badgeUrl',
            'consoleName',
            'gameSystemIconSrc',
            'highestProgressionAwardDate',
            'highestProgressionStatus',
            'isEvent',
            'isHub',
            'lastUpdated',
            'pointsSum',
            'rawTitle',
            'retroPointsSum',
            'retroRatio',
        );
    }

    /**
     * Builds an array of high-level achievement metadata for the game card.
     * The resulting array contains the total points, total RetroPoints, retro ratio, and
     * last updated date. If no achievements are found, the points sums and retro ratio are
     * set to 0 and the last updated date comes from the game metadata instead of the
     * achievement metadata.
     *
     * @return array an array containing total points, total RetroPoints, retro ratio, and last updated date
     */
    private function buildCardAchievementsData(array $rawAchievements, string $gameLastUpdated): array
    {
        $pointsSum = 0;
        $retroPointsSum = 0;
        $retroRatio = 0;
        $lastUpdated = Carbon::parse($gameLastUpdated);

        if (!empty($rawAchievements)) {
            $lastUpdated = Carbon::parse($rawAchievements[0]['DateModified']);

            foreach ($rawAchievements as $achievement) {
                $pointsSum += $achievement['Points'];
                $retroPointsSum += $achievement['TrueRatio'];
                $achievementDate = Carbon::parse($achievement['DateModified']);
                if ($achievementDate->gt($lastUpdated)) {
                    $lastUpdated = $achievementDate;
                }
            }

            $retroRatio = number_format($retroPointsSum / ($pointsSum ?: 1), 2, '.', '');
        }

        return [$pointsSum, $retroPointsSum, $retroRatio, $lastUpdated];
    }

    /**
     * Builds an array containing the highest progression status and corresponding award date for a game.
     *
     * @param ?array $userHighestGameAward an array containing `highestAwardKind` and `highestAward`, see PlayerBadge::getHighestUserAwardForGameId()
     * @param bool $isEvent whether or not the game ID is associated with the "Events" console
     */
    private function buildCardUserProgressionData(?array $userHighestGameAward, bool $isEvent): array
    {
        $highestProgressionStatus = null;
        $highestProgressionAwardDate = null;

        if ($userHighestGameAward) {
            // 'beaten-softcore', 'beaten-hardcore', 'completed', 'mastered'
            $highestProgressionStatus = $userHighestGameAward['highestAwardKind'];

            $highestProgressionAwardDate = Carbon::parse($userHighestGameAward['highestAward']->AwardDate);
        }

        return [$highestProgressionStatus, $highestProgressionAwardDate];
    }
}
