<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\PlayerStat;
use App\Models\User;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Services\UserTicketCountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
        private int $totalCasualAchievements = 0,
        private ?array $userClaims = null,
    ) {
    }

    public function render(): View
    {
        $hardcorePoints = $this->userMassData['TotalPoints'] ?? 0;
        $casualPoints = $this->userMassData['TotalSoftcorePoints'] ?? 0;

        $hardcoreRankMeta = ['rank' => 0];
        $casualRankMeta = ['rank' => 0];
        if ($hardcorePoints >= Rank::MIN_POINTS) {
            $hardcoreRankMeta = $this->buildRankMetadata($this->user, predefinedRank: $this->userMassData['Rank']);
        }
        if ($casualPoints >= Rank::MIN_POINTS) {
            $casualRankMeta = $this->buildRankMetadata($this->user, RankType::Casual);
        }

        $developerStats = [];
        // FIXME: Uses legacy roles.
        if ($this->user->yield_unlocks > 0 || $this->user->getAttribute('Permissions') >= Permissions::JuniorDeveloper) {
            $developerStats = $this->buildDeveloperStats($this->user, $this->userMassData);
        }

        return view('components.user.profile-meta', [
            'developerStats' => $developerStats,
            'hardcoreRankMeta' => $hardcoreRankMeta,
            'playerStats' => $this->buildPlayerStats($this->user, $this->userMassData, $hardcoreRankMeta, $casualRankMeta),
            'socialStats' => $this->buildSocialStats($this->user),
            'casualRankMeta' => $casualRankMeta,
            'user' => $this->user,
            'userClaims' => $this->userClaims,
            'userMassData' => $this->userMassData, // TODO: replace w/ props from user model
            'username' => $this->user->username, // TODO: remove
        ]);
    }

    private function buildDeveloperStats(User $user, array $userMassData): array
    {
        // Achievement sets worked on
        $gameAuthoredAchievementsCount = $user->authoredAchievements()
            ->promoted()
            ->select(DB::raw('COUNT(DISTINCT game_id) as game_count'))
            ->first()
            ->game_count;
        $setsWorkedOnStat = [
            'label' => 'Achievement sets worked on',
            'value' => localized_number($gameAuthoredAchievementsCount),
            'href' => $gameAuthoredAchievementsCount ? route('developer.sets', ['user' => $user->display_name]) : null,
            'isMuted' => !$gameAuthoredAchievementsCount,
        ];

        // Achievements unlocked by players
        $achievementsUnlockedByPlayersStat = [
            'label' => 'Achievements unlocked by players',
            'value' => localized_number($userMassData['ContribCount']),
            'href' => $userMassData['ContribCount'] > 0 ? route('user.achievement-author.feed', ['user' => $user->display_name]) : null,
            'isMuted' => !$userMassData['ContribCount'],
        ];

        // Points awarded to players
        $pointsAwardedToPlayersStat = [
            'label' => 'Points awarded to players',
            'value' => localized_number($userMassData['ContribYield']),
            'href' => $userMassData['ContribYield'] > 0 ? route('user.achievement-author.feed', ['user' => $user->display_name]) : null,
            'isMuted' => !$userMassData['ContribYield'],
        ];

        // Code notes created
        $totalAuthoredCodeNotes = $user->authoredCodeNotes()->count();
        $codeNotesCreatedStat = [
            'label' => 'Code notes created',
            'value' => localized_number($totalAuthoredCodeNotes),
            'href' => $totalAuthoredCodeNotes ? '/individualdevstats.php?u=' . $user->display_name . '#code-notes' : null,
            'isMuted' => !$totalAuthoredCodeNotes,
        ];

        // Leaderboards created
        $totalAuthoredLeaderboards = $user->authoredLeaderboards()
            ->select(DB::raw('COUNT(leaderboards.id) AS TotalAuthoredLeaderboards'))
            ->value('TotalAuthoredLeaderboards');
        $leaderboardsCreatedStat = [
            'label' => 'Leaderboards created',
            'value' => localized_number($totalAuthoredLeaderboards),
            'href' => $totalAuthoredLeaderboards ? '/individualdevstats.php?u=' . $user->display_name : null,
            'isMuted' => !$totalAuthoredLeaderboards,
        ];

        // Open tickets
        $openTickets = null;
        if ($user->yield_unlocks) {
            $openTickets = array_sum(app(UserTicketCountService::class)->countOpenForDev($user));
        }
        $openTicketsStat = [
            'label' => 'Open tickets',
            'value' => $openTickets === null ? "Tickets can't be assigned to {$user->display_name}." : localized_number($openTickets),
            'href' => $openTickets ? route('developer.tickets', ['user' => $user->display_name]) : null,
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
        array $casualRankMeta,
    ): array {
        $hardcorePoints = $userMassData['TotalPoints'] ?? 0;
        $casualPoints = $userMassData['TotalSoftcorePoints'] ?? 0;
        $preferredMode = $casualPoints > $hardcorePoints ? 'casual' : 'hardcore';

        $recentPointsEarned = $this->calculateRecentPointsEarned($user, $preferredMode);

        // Total games beaten
        $gamesBeatenStats = PlayerStat::where('user_id', $user->id)
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
            'href' => $retailGamesBeaten ? route('ranking.beaten-games', ['filter[user]' => $user->display_name]) : null,
        ];

        // Started games beaten
        $startedGamesBeatenPercentage = $this->calculateStartedGamesBeaten($this->userJoinedGamesAndAwards);
        $startedGamesBeatenPercentageStat = [
            'label' => 'Started games beaten',
            'value' => $startedGamesBeatenPercentage . '%',
            'isMuted' => $hardcorePoints === 0 && $casualPoints === 0,
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
            $preferredMode !== 'casual',
            $preferredMode !== 'casual' ? $hardcorePoints : $casualPoints,
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
            $this->buildCasualPlayerStats($user, $userMassData, $casualRankMeta),
        );
    }

    private function buildRankMetadata(
        User $user,
        RankType $rankType = RankType::Hardcore,
        ?int $predefinedRank = null,
    ): array {
        $rank = $predefinedRank ?? getUserRank($user->username, $rankType);
        $numRankedUsers = countRankedUsers($rankType);

        if ($rank === null || $numRankedUsers === 0) {
            return [
                'rank' => 0,
                'numRankedUsers' => 0,
                'rankPercent' => '0.00',
                'rankPercentLabel' => '',
                'rankOffset' => 0,
            ];
        }

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
        $numForumPosts = $user->forumPosts()->authorized()->viewable(Auth::user())->count();

        // Forum posts
        $forumPostsStat = [
            'label' => 'Forum posts',
            'value' => localized_number($numForumPosts),
            'href' => $numForumPosts ? route('user.posts.index', ['user' => $user->display_name]) : null,
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
            'href' => $setRequests['used'] !== 0
                ? route('game.request.user', ['user' => $user->display_name])
                : null,
            'isMuted' => $setRequests['used'] === 0,
        ];

        return compact(
            'forumPostsStat',
            'setsRequestedStat',
        );
    }

    private function buildCasualPlayerStats(User $user, array $userMassData, array $casualRankMeta): array
    {
        $casualPoints = $userMassData['TotalSoftcorePoints'] ?? 0;

        // Casual points.
        $casualPointsStat = [
            'label' => 'Points (casual)',
            'value' => localized_number($casualPoints),
            'isMuted' => !$casualPoints,
        ];

        // Casual site rank.
        $casualSiteRankHrefLabel = null;
        $casualSiteRankValue = '';
        if ($userMassData['Untracked']) {
            $casualSiteRankValue = 'Untracked';
        } elseif ($casualPoints > 0 && !$casualRankMeta['rank']) {
            $casualSiteRankValue = 'requires ' . Rank::MIN_POINTS . ' points';
        } elseif ($casualPoints === 0 && !$casualRankMeta['rank']) {
            $casualSiteRankValue = 'none';
        } else {
            $casualSiteRankHrefLabel = "#" . localized_number($casualRankMeta['rank']);
            $casualSiteRankValue = "of " . localized_number($casualRankMeta['numRankedUsers']);
        }
        $casualSiteRankStat = [
            'label' => 'Casual rank',
            'hrefLabel' => $casualSiteRankHrefLabel,
            'value' => $casualSiteRankValue,
            'isMuted' => (
                $userMassData['Untracked']
                || ($casualPoints > 0 && !$casualRankMeta['rank'])
                || ($casualPoints === 0 && !$casualRankMeta['rank'])
            ),
            'shouldEnableBolding' => false,
            'href' => !$userMassData['Untracked'] && isset($casualRankMeta['rankOffset'])
                ? '/globalRanking.php?t=2&o=' . $casualRankMeta['rankOffset'] . '&s=2'
                : null,
        ];

        // Achievements unlocked (casual).
        $casualAchievementsUnlockedStat = [
            'label' => 'Achievements unlocked (casual)',
            'value' => localized_number($this->totalCasualAchievements),
            'isMuted' => !$this->totalCasualAchievements,
        ];

        return compact(
            'casualAchievementsUnlockedStat',
            'casualPointsStat',
            'casualSiteRankStat',
        );
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

        $weeksSinceFirstUnlock = (int) $startingDate->diffInWeeks(Carbon::now(), true);

        // Avoid division by zero.
        if ($weeksSinceFirstUnlock <= 0) {
            return $points;
        }

        $averagePointsPerWeek = $points / $weeksSinceFirstUnlock;

        return (int) round($averagePointsPerWeek);
    }

    private function calculateRecentPointsEarned(User $user, string $preferredMode = 'hardcore'): array
    {
        $dateColumn = $preferredMode === 'hardcore' ? 'unlocked_hardcore_at' : 'unlocked_at';

        $pointsLast7Days = (int) Achievement::query()
            ->whereHas('game', function ($query) {
                $query->whereGameSystem();
            })
            ->whereIn('id', function ($query) use ($user, $dateColumn) {
                $sevenDaysAgo = now()->subDays(7)->startOfDay();
                $query->select('achievement_id')
                    ->from('player_achievements')
                    ->where($dateColumn, '>=', $sevenDaysAgo)
                    ->where('user_id', $user->id);
            })
            ->sum('points');

        $pointsLast30Days = (int) Achievement::query()
            ->whereHas('game', function ($query) {
                $query->whereGameSystem();
            })
            ->whereIn('id', function ($query) use ($user, $dateColumn) {
                $thirtyDaysAgo = now()->subDays(30)->startOfDay();
                $query->select('achievement_id')
                    ->from('player_achievements')
                    ->where($dateColumn, '>=', $thirtyDaysAgo)
                    ->where('user_id', $user->id);
            })
            ->sum('points');

        return compact('pointsLast30Days', 'pointsLast7Days');
    }

    private function calculateStartedGamesBeaten(array $userJoinedGamesAndAwards): string
    {
        $totalGames = 0;
        $beatenGames = 0;

        foreach ($userJoinedGamesAndAwards as $game) {
            if (mb_strpos($game['Title'], '[Subset') !== false || mb_strpos($game['Title'], '~Test Kit~')) {
                continue;
            }

            $totalGames++;

            if (isset($game['HighestAwardKind'])) {
                $beatenGames++;
            }
        }

        $averageFinishedGames = 0;
        if ($totalGames > 0) {
            $averageFinishedGames = ($beatenGames / $totalGames) * 100;
        }

        return number_format($averageFinishedGames, 2, '.', '');
    }
}
