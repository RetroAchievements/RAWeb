<?php

declare(strict_types=1);

namespace App\View\Components\Platform\Cards;

use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimStatus;
use App\Community\Models\AchievementSetClaim;
use App\Platform\Models\Game as GameModel;
use App\Platform\Models\GameAlternative;
use App\Platform\Models\PlayerBadge;
use App\Support\Cache\CacheKey;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class Game extends Component
{
    private int $gameId;
    private int $hubConsoleId = 100;
    private array $userGameProgressionAwards;
    private ?string $usernameContext;

    public function __construct(int $gameId, ?string $targetUsername = null)
    {
        $this->gameId = $gameId;
        $this->usernameContext = $targetUsername ?? Auth::user()->User ?? null;
    }

    public function render(): ?View
    {
        $rawGameData = $this->getGameData($this->gameId);

        if (!$rawGameData) {
            return null;
        }

        $this->userGameProgressionAwards = $this->getUserGameProgressionAwards(
            $this->gameId,
            $this->usernameContext
        );

        $cardViewValues = $this->buildAllCardViewValues(
            $rawGameData,
            $this->userGameProgressionAwards
        );

        return view('platform.components.cards.game', $cardViewValues);
    }

    /**
     * @return array<mixed>
     */
    private function getGameData(int $gameId): ?array
    {
        $cacheKey = CacheKey::buildGameCardDataCacheKey($gameId);

        if (Cache::store('array')->has($cacheKey)) {
            return Cache::store('array')->get($cacheKey);
        }

        $loadGameCardData = (function () use ($gameId): ?array {
            $foundGame = GameModel::with([
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

            $foundClaims = AchievementSetClaim::where('GameID', $gameId)->get()->toArray();

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

        Cache::store('array')->put($cacheKey, $loadGameCardData, Carbon::now()->addDays(7));

        return $loadGameCardData;
    }

    private function getUserGameProgressionAwards(int $gameId, ?string $usernameContext): array
    {
        $userGameProgressionAwards = ['Completed' => null, 'Mastered' => null];

        if ($usernameContext) {
            $foundBadges = PlayerBadge::where('User', '=', $usernameContext)
                ->where('AwardData', '=', $gameId)
                ->get();

            foreach ($foundBadges as $badge) {
                if ($badge->AwardType === AwardType::Mastery) {
                    if ($badge['AwardDataExtra'] === 0 && is_null($userGameProgressionAwards['Completed'])) {
                        $userGameProgressionAwards['Completed'] = $badge;
                    }

                    if ($badge['AwardDataExtra'] === 1 && is_null($userGameProgressionAwards['Mastered'])) {
                        $userGameProgressionAwards['Mastered'] = $badge;
                    }
                }
            }
        }

        return $userGameProgressionAwards;
    }

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

    private function buildAllCardViewValues(array $rawGameData, array $userGameProgressionAwards): array
    {
        $rawTitle = $rawGameData['Title'];
        $renderedTitle = renderGameTitle($rawTitle);
        $badgeUrl = media_asset($rawGameData['ImageIcon']);
        $gameSystemIconSrc = getSystemIconUrl($rawGameData['ConsoleID']);
        $consoleName = $rawGameData['ConsoleName'];
        $achievementsCount = count($rawGameData['Achievements']);
        $isHub = $rawGameData['ConsoleID'] === $this->hubConsoleId;
        $altGamesCount = count($rawGameData['AltGames']);

        [$pointsSum, $retroPointsSum, $retroRatio, $lastUpdated] = $this->buildCardAchievementsData(
            $rawGameData['Achievements'],
            $rawGameData['Updated'],
        );

        if ($isHub) {
            $lastUpdated = $this->buildCardLastUpdatedFromAltGames($rawGameData['AltGames'], $lastUpdated);
        }

        [$highestProgressionStatus, $highestProgressionAwardDate] = $this->buildCardUserProgressionData($userGameProgressionAwards);

        $activeClaims = array_filter($rawGameData['Claims'], fn ($claim) => $claim['Status'] === ClaimStatus::Active);
        $activeDeveloperUsernames = array_map(fn ($activeClaim) => $activeClaim['User'], array_values($activeClaims));
        $activeDevelopersLabel = $this->buildActiveDevelopersLabel($activeDeveloperUsernames);

        return compact(
            'isHub',
            'altGamesCount',
            'rawTitle',
            'renderedTitle',
            'badgeUrl',
            'gameSystemIconSrc',
            'consoleName',
            'achievementsCount',
            'pointsSum',
            'retroPointsSum',
            'retroRatio',
            'lastUpdated',
            'highestProgressionStatus',
            'highestProgressionAwardDate',
            'activeDeveloperUsernames',
            'activeDevelopersLabel',
        );
    }

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

    private function buildCardLastUpdatedFromAltGames(array $rawAltGames, Carbon $lastUpdated): Carbon
    {
        foreach ($rawAltGames as $altGame) {
            $altGameUpdatedDate = Carbon::parse($altGame['Updated']);
            if ($altGameUpdatedDate->gt($lastUpdated)) {
                $lastUpdated = $altGameUpdatedDate;
            }
        }

        return $lastUpdated;
    }

    private function buildCardUserProgressionData(array $userGameProgressionAwards): array
    {
        $highestProgressionStatus = null;
        $highestProgressionAwardDate = null;

        if ($userGameProgressionAwards['Completed']) {
            $highestProgressionStatus = 'Completed';
            $highestProgressionAwardDate = Carbon::parse($userGameProgressionAwards['Completed']['AwardDate']);
        }

        if ($userGameProgressionAwards['Mastered']) {
            $highestProgressionStatus = 'Mastered';
            $highestProgressionAwardDate = Carbon::parse($userGameProgressionAwards['Mastered']['AwardDate']);
        }

        return [$highestProgressionStatus, $highestProgressionAwardDate];
    }
}
