<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Enums\Permissions;
use App\Models\PlayerStat;
use App\Models\User;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class UserProfileMeta extends Component
{
    public function __construct(
        private User $user,
        private string $averageCompletionPercentage = '0.00',
        private array $userJoinedGamesAndAwards = [],
        private array $userMassData = [],
        private int $totalHardcoreAchievements = 0,
        private int $totalSoftcoreAchievements = 0,
        private ?array $userClaims = null,
    ) {
    }

    public function render(): View
    {
        $hardcorePoints = $this->userMassData['TotalPoints'] ?? 0;
        $softcorePoints = $this->userMassData['TotalSoftcorePoints'] ?? 0;

        $hardcoreRankMeta = ['rank' => 0];
        $softcoreRankMeta = ['rank' => 0];
        if ($hardcorePoints >= Rank::MIN_POINTS) {
            $hardcoreRankMeta = $this->buildRankMetadata($this->user, predefinedRank: $this->userMassData['Rank']);
        }
        if ($softcorePoints >= Rank::MIN_POINTS) {
            $softcoreRankMeta = $this->buildRankMetadata($this->user, RankType::Softcore);
        }

        $developerStats = [];
        // FIXME: Uses legacy roles.
        if ($this->userMassData['ContribCount'] > 0 || $this->user->getAttribute('Permissions') >= Permissions::JuniorDeveloper) {
            $developerStats = $this->buildDeveloperStats($this->user, $this->userMassData);
        }

        $this->calculateRecentPointsEarned($this->user);

        return view('components.user.profile-meta', [
            'developerStats' => $developerStats,
            'hardcoreRankMeta' => $hardcoreRankMeta,
            'playerStats' => $this->buildPlayerStats($this->user, $this->userMassData, $hardcoreRankMeta, $softcoreRankMeta, $this->userJoinedGamesAndAwards),
            'socialStats' => $this->buildSocialStats($this->user),
            'softcoreRankMeta' => $softcoreRankMeta,
            'user' => $this->user,
            'userClaims' => $this->userClaims,
            'userMassData' => $this->userMassData, // TODO: replace w/ props from user model
            'username' => $this->user->User, // TODO: remove
        ]);
    }

    private function buildDeveloperStats(User $user, array $userMassData): array
    {
        // Achievement sets worked on
        $gameAuthoredAchievementsCount = $user->authoredAchievements()
            ->published()
            ->select(DB::raw('COUNT(DISTINCT GameID) as game_count'))
            ->first()
            ->game_count;
        $setsWorkedOnStat = [
            'label' => 'Achievement sets worked on',
            'value' => localized_number($gameAuthoredAchievementsCount),
            'href' => $gameAuthoredAchievementsCount ? route('developer.sets', ['user' => $user]) : null,
            'isMuted' => !$gameAuthoredAchievementsCount,
        ];

        // Achievements unlocked by players
        $achievementsUnlockedByPlayersStat = [
            'label' => 'Achievements unlocked by players',
            'value' => localized_number($userMassData['ContribCount']),
            'href' => $userMassData['ContribCount'] > 0 ? route('developer.feed', ['user' => $user]) : null,
            'isMuted' => !$userMassData['ContribCount'],
        ];

        // Points awarded to players
        $pointsAwardedToPlayersStat = [
            'label' => 'Points awarded to players',
            'value' => localized_number($userMassData['ContribYield']),
            'href' => $userMassData['ContribYield'] > 0 ? route('developer.feed', ['user' => $user]) : null,
            'isMuted' => !$userMassData['ContribYield'],
        ];

        // Code notes created
        $totalAuthoredCodeNotes = $user->authoredCodeNotes()->count();
        $codeNotesCreatedStat = [
            'label' => 'Code notes created',
            'value' => localized_number($totalAuthoredCodeNotes),
            'href' => $totalAuthoredCodeNotes ? '/individualdevstats.php?u=' . $user->User . '#code-notes' : null,
            'isMuted' => !$totalAuthoredCodeNotes,
        ];

        // Leaderboards created
        $totalAuthoredLeaderboards = $user->authoredLeaderboards()
            ->select(DB::raw('COUNT(LeaderboardDef.ID) AS TotalAuthoredLeaderboards'))
            ->value('TotalAuthoredLeaderboards');
        $leaderboardsCreatedStat = [
            'label' => 'Leaderboards created',
            'value' => localized_number($totalAuthoredLeaderboards),
            'href' => $totalAuthoredLeaderboards ? '/individualdevstats.php?u=' . $user->User : null,
            'isMuted' => !$totalAuthoredLeaderboards,
        ];

        // Open tickets
        $openTickets = null;
        if ($user->ContribCount) {
            $openTickets = array_sum(countOpenTicketsByDev($user));
        }
        $openTicketsStat = [
            'label' => 'Open tickets',
            'value' => $openTickets === null ? "Tickets can't be assigned to {$user->User}." : localized_number($openTickets),
            'href' => $openTickets ? route('developer.tickets', ['user' => $user]) : null,
            'isMuted' => !$openTickets,
        ];

        return compact(
            'achievementsUnlockedByPlayersStat',
            'codeNotesCreatedStat',
            'leaderboardsCreatedStat',
            'openTicketsStat',
            'pointsAwardedToPlayersStat',
            'setsWorkedOnStat',
        );
    }

    private function buildHardcorePlayerStats(User $user, array $userMassData, array $hardcoreRankMeta): array
    {
        $hardcorePoints = $userMassData['TotalPoints'] ?? 0;
        $weightedPoints = $userMassData['TotalTruePoints'] ?? 0;
        $retroRatio = $hardcorePoints ? sprintf("%01.2f", $weightedPoints / $hardcorePoints) : null;

        // Hardcore points
        $hardcorePointsStat = [
            'label' => 'Points',
            'value' => localized_number($hardcorePoints),
            'weightedPoints' => $userMassData['TotalTruePoints'],
            'isMuted' => !$hardcorePoints,
        ];

        // Hardcore site rank
        $hardcoreSiteRankHrefLabel = null;
        $hardcoreSiteRankValue = '';
        if ($userMassData['Untracked']) {
            $hardcoreSiteRankValue = 'Untracked';
        } elseif ($hardcorePoints > 0 && !$hardcoreRankMeta['rank']) {
            $hardcoreSiteRankValue = 'requires ' . Rank::MIN_POINTS . ' points';
        } elseif ($hardcorePoints === 0 && !$hardcoreRankMeta['rank']) {
            $hardcoreSiteRankValue = 'none';
        } else {
            $hardcoreSiteRankHrefLabel = "#" . localized_number($hardcoreRankMeta['rank']);
            $hardcoreSiteRankValue = "of " . localized_number($hardcoreRankMeta['numRankedUsers']);
        }
        $hardcoreSiteRankStat = [
            'label' => 'Site rank',
            'hrefLabel' => $hardcoreSiteRankHrefLabel,
            'value' => $hardcoreSiteRankValue,
            'isMuted' => (
                $userMassData['Untracked']
                || ($hardcorePoints > 0 && !$hardcoreRankMeta['rank'])
                || ($hardcorePoints === 0 && !$hardcoreRankMeta['rank'])
            ),
            'shouldEnableBolding' => false,
            'href' => !$userMassData['Untracked'] && isset($hardcoreRankMeta['rankOffset'])
                ? '/globalRanking.php?t=2&o=' . $hardcoreRankMeta['rankOffset'] . '&s=5'
                : null,
        ];

        // Achievements unlocked
        $hardcoreAchievementsUnlockedStat = [
            'label' => 'Achievements unlocked',
            'value' => localized_number($this->totalHardcoreAchievements),
            'isMuted' => !$this->totalHardcoreAchievements,
        ];

        // RetroRatio
        $retroRatioStat = [
            'label' => 'RetroRatio',
            'value' => $retroRatio ?? 'none',
            'isMuted' => !$retroRatio,
        ];

        return compact(
            'hardcoreAchievementsUnlockedStat',
            'hardcorePointsStat',
            'hardcoreSiteRankStat',
            'retroRatioStat',
        );
    }

    private function buildPlayerStats(
        User $user,
        array $userMassData,
        array $hardcoreRankMeta,
        array $softcoreRankMeta,
        array $userJoinedGamesAndAwards
    ): array {
        $hardcorePoints = $userMassData['TotalPoints'] ?? 0;
        $softcorePoints = $userMassData['TotalSoftcorePoints'] ?? 0;
        $preferredMode = $softcorePoints > $hardcorePoints ? 'softcore' : 'hardcore';

        $recentPointsEarned = $this->calculateRecentPointsEarned($user, $preferredMode);

        // Total games beaten
        $gamesBeatenStats = PlayerStat::where('user_id', $user->ID)
            ->where('system_id', null)
            ->whereIn('type', [
                PlayerStatType::GamesBeatenHardcoreDemos,
                PlayerStatType::GamesBeatenHardcoreHacks,
                PlayerStatType::GamesBeatenHardcoreHomebrew,
                PlayerStatType::GamesBeatenHardcorePrototypes,
                PlayerStatType::GamesBeatenHardcoreRetail,
                PlayerStatType::GamesBeatenHardcoreUnlicensed,
            ])
            ->selectRaw('
                SUM(value) as totalGamesBeaten,
                SUM(CASE WHEN type IN (?, ?) THEN value ELSE 0 END) AS retailGamesBeaten',
                [
                    PlayerStatType::GamesBeatenHardcoreRetail,
                    PlayerStatType::GamesBeatenHardcoreUnlicensed,
                ]
            )
            ->first();
        $totalGamesBeaten = (int) $gamesBeatenStats->totalGamesBeaten;
        $retailGamesBeaten = (int) $gamesBeatenStats->retailGamesBeaten;
        $totalGamesBeatenStat = [
            'label' => 'Total games beaten',
            'hrefLabel' => "(" . localized_number($retailGamesBeaten) . " retail)",
            'value' => localized_number($totalGamesBeaten),
            'isHrefLabelBeforeLabel' => false,
            'isMuted' => !$totalGamesBeaten,
            'href' => $retailGamesBeaten ? route('ranking.beaten-games', ['filter[user]' => $user->username]) : null,
        ];

        // Started games beaten
        $startedGamesBeatenPercentage = $this->calculateAverageFinishedGames($this->userJoinedGamesAndAwards);
        $startedGamesBeatenPercentageStat = [
            'label' => 'Started games beaten',
            'value' => $startedGamesBeatenPercentage . '%',
            'isMuted' => $hardcorePoints === 0 && $softcorePoints === 0,
        ];

        // Points earned in the last 7 days
        $pointsLast7DaysStat = [
            'label' => 'Points earned in the last 7 days',
            'value' => localized_number($recentPointsEarned['pointsLast7Days']),
            'isMuted' => !$recentPointsEarned['pointsLast7Days'],
        ];

        // Points earned in the last 30 days
        $pointsLast30DaysStat = [
            'label' => 'Points earned in the last 30 days',
            'value' => localized_number($recentPointsEarned['pointsLast30Days']),
            'isMuted' => !$recentPointsEarned['pointsLast30Days'],
        ];

        // Average points per week
        $averagePointsPerWeek = $this->calculateAveragePointsPerWeek(
            $this->user,
            $preferredMode !== "softcore",
            $preferredMode !== "softcore" ? $hardcorePoints : $softcorePoints,
        );
        $averagePointsPerWeekStat = [
            'label' => 'Average points per week',
            'value' => localized_number($averagePointsPerWeek),
            'isMuted' => !$averagePointsPerWeek,
        ];

        // Average completion percentage
        $averageCompletionStat = [
            'label' => 'Average completion',
            'value' => $this->averageCompletionPercentage . "%",
            'isMuted' => $this->averageCompletionPercentage === '0.0',
        ];

        return array_merge(
            compact(
                'averageCompletionStat',
                'averagePointsPerWeekStat',
                'pointsLast30DaysStat',
                'pointsLast7DaysStat',
                'startedGamesBeatenPercentageStat',
                'totalGamesBeatenStat',
            ),
            $this->buildHardcorePlayerStats($user, $userMassData, $hardcoreRankMeta),
            $this->buildSoftcorePlayerStats($user, $userMassData, $softcoreRankMeta),
        );
    }

    private function buildRankMetadata(
        User $user,
        int $rankType = RankType::Hardcore,
        ?int $predefinedRank = null
    ): array {
        $rank = $predefinedRank ?? getUserRank($user->User, $rankType);
        $numRankedUsers = countRankedUsers($rankType);
        $rankPercent = sprintf("%1.2f", ($rank / $numRankedUsers) * 100.0);
        $rankPercentLabel = $rank > 100 ? "(Top $rankPercent%)" : "";
        $rankOffset = (int) (($rank - 1) / 25) * 25;

        return compact(
            'rank',
            'numRankedUsers',
            'rankPercent',
            'rankPercentLabel',
            'rankOffset',
        );
    }

    private function buildSocialStats(User $user): array
    {
        $userSetRequestInformation = getUserRequestsInformation($user);
        $numForumPosts = $user->forumPosts()->count();

        // Forum posts
        $forumPostsStat = [
            'label' => 'Forum posts',
            'value' => localized_number($numForumPosts),
            'href' => $numForumPosts ? route('user.posts', ['user' => $user]) : null,
            'isMuted' => $numForumPosts === 0,
        ];

        // Achievement sets requested
        $setRequests = $userSetRequestInformation;
        $setsRequestedValue = $setRequests['used'] === 0 && $setRequests['remaining'] > 0
            ? "0 ({$setRequests['remaining']} left)"
            : $setRequests['used'] . ($setRequests['remaining'] > 0 ? " ({$setRequests['remaining']} left)" : "");

        $setsRequestedStat = [
            'label' => 'Achievement sets requested',
            'value' => $setsRequestedValue,
            'href' => $setRequests['used'] !== 0 ? "/setRequestList.php?u={$user->User}" : null,
            'isMuted' => $setRequests['used'] === 0,
        ];

        return compact(
            'forumPostsStat',
            'setsRequestedStat',
        );
    }

    private function buildSoftcorePlayerStats(User $user, array $userMassData, array $softcoreRankMeta): array
    {
        $softcorePoints = $userMassData['TotalSoftcorePoints'] ?? 0;

        // Softcore points
        $softcorePointsStat = [
            'label' => 'Points (softcore)',
            'value' => localized_number($softcorePoints),
            'isMuted' => !$softcorePoints,
        ];

        // Softcore site rank
        $softcoreSiteRankHrefLabel = null;
        $softcoreSiteRankValue = '';
        if ($userMassData['Untracked']) {
            $softcoreSiteRankValue = 'Untracked';
        } elseif ($softcorePoints > 0 && !$softcoreRankMeta['rank']) {
            $softcoreSiteRankValue = 'requires ' . Rank::MIN_POINTS . ' points';
        } elseif ($softcorePoints === 0 && !$softcoreRankMeta['rank']) {
            $softcoreSiteRankValue = 'none';
        } else {
            $softcoreSiteRankHrefLabel = "#" . localized_number($softcoreRankMeta['rank']);
            $softcoreSiteRankValue = "of " . localized_number($softcoreRankMeta['numRankedUsers']);
        }
        $softcoreSiteRankStat = [
            'label' => 'Softcore rank',
            'hrefLabel' => $softcoreSiteRankHrefLabel,
            'value' => $softcoreSiteRankValue,
            'isMuted' => (
                $userMassData['Untracked']
                || ($softcorePoints > 0 && !$softcoreRankMeta['rank'])
                || ($softcorePoints === 0 && !$softcoreRankMeta['rank'])
            ),
            'shouldEnableBolding' => false,
            'href' => !$userMassData['Untracked'] && isset($softcoreRankMeta['rankOffset'])
                ? '/globalRanking.php?t=2&o=' . $softcoreRankMeta['rankOffset'] . '&s=2'
                : null,
        ];

        // Achievements unlocked (softcore)
        $softcoreAchievementsUnlockedStat = [
            'label' => 'Achievements unlocked (softcore)',
            'value' => localized_number($this->totalSoftcoreAchievements),
            'isMuted' => !$this->totalSoftcoreAchievements,
        ];

        return compact(
            'softcoreAchievementsUnlockedStat',
            'softcorePointsStat',
            'softcoreSiteRankStat',
        );
    }

    private function calculateAverageFinishedGames(array $userJoinedGamesAndAwards): string
    {
        $totalGames = 0;
        $finishedGames = 0;

        // Iterate over each game to check if it is finished.
        foreach ($userJoinedGamesAndAwards as $game) {
            // Ignore subsets and test kits.
            if (mb_strpos($game['Title'], '[Subset') !== false || mb_strpos($game['Title'], '~Test Kit~')) {
                continue;
            }

            $totalGames++;

            if (isset($game['HighestAwardKind']) && $game['HighestAwardKind'] !== null) {
                $finishedGames++;
            }
        }

        // Calculate the average percentage of finished games.
        $averageFinishedGames = 0;
        if ($totalGames > 0) {
            $averageFinishedGames = ($finishedGames / $totalGames) * 100;
        }

        // Format and return the result to 2 decimal places.
        return number_format($averageFinishedGames, 2, '.', '');
    }

    private function calculateAveragePointsPerWeek(User $user, bool $doesUserPreferHardcore, int $points = 0): int
    {
        $field = $doesUserPreferHardcore ? "unlocked_hardcore_at" : "unlocked_at";

        $startingDate = $user->playerAchievements()
            ->whereNotNull($field)
            ->orderBy($field)
            ->value($field);

        if (is_null($startingDate)) {
            return 0;
        }

        $weeksSinceFirstUnlock = $startingDate->diffInWeeks(Carbon::now());

        // Avoid division by zero.
        if ($weeksSinceFirstUnlock <= 0) {
            return $points;
        }

        $averagePointsPerWeek = $points / $weeksSinceFirstUnlock;

        return (int) round($averagePointsPerWeek);
    }

    private function calculateRecentPointsEarned(User $user, string $preferredMode = 'hardcore'): array
    {
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        $dateColumn = $preferredMode === 'hardcore' ? 'unlocked_hardcore_at' : 'unlocked_at';

        $achievements = $user->playerAchievements()
            ->with('achievement')
            ->where($dateColumn, '>=', $thirtyDaysAgo)
            ->get();

        $pointsLast30Days = 0;
        $pointsLast7Days = 0;

        $now = now();

        foreach ($achievements as $playerAchievement) {
            $achievementDate = $playerAchievement->{$dateColumn};
            $daysAgo = abs($now->diffInDays($achievementDate, false));

            if ($daysAgo <= 7) {
                $pointsLast7Days += $playerAchievement->achievement->points;
            }

            $pointsLast30Days += $playerAchievement->achievement->points;
        }

        return compact('pointsLast30Days', 'pointsLast7Days');
    }
}
