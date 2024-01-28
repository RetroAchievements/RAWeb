<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
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
        $username = $this->user->User;
        $hardcorePoints = $this->userMassData['TotalPoints'] ?? 0;
        $softcorePoints = $this->userMassData['TotalSoftcorePoints'] ?? 0;

        $hardcoreRankMeta = ['rank' => 0];
        $softcoreRankMeta = ['rank' => 0];
        if ($hardcorePoints >= Rank::MIN_POINTS) {
            $hardcoreRankMeta = $this->buildRankMetadata($username, predefinedRank: $this->userMassData['Rank']);
        }
        if ($softcorePoints >= Rank::MIN_POINTS) {
            $softcoreRankMeta = $this->buildRankMetadata($username, RankType::Softcore);
        }

        $preferredMode = $softcorePoints > $hardcorePoints ? 'softcore' : 'hardcore';

        $averagePointsPerWeek = $this->calculateAveragePointsPerWeek(
            $this->userMassData['MemberSince'],
            // Calculate based on the user's primary playing mode.
            $preferredMode === 'softcore' ? $softcorePoints : $hardcorePoints,
        );

        $developerStats = [];
        // FIXME: Uses legacy roles.
        if ($this->userMassData['ContribCount'] > 0 || $this->user->getAttribute('Permissions') >= Permissions::JuniorDeveloper) {
            $developerStats = $this->buildDeveloperStats($this->user);
        }

        $this->calculateRecentPointsEarned($this->user);

        return view('community.components.user.profile-meta', [
            'averageCompletionPercentage' => $this->averageCompletionPercentage,
            'averageFinishedGames' => $this->calculateAverageFinishedGames($this->userJoinedGamesAndAwards),
            'averagePointsPerWeek' => $averagePointsPerWeek,
            'developerStats' => $developerStats,
            'hardcoreRankMeta' => $hardcoreRankMeta,
            'recentPointsEarned' => $this->calculateRecentPointsEarned($this->user, $preferredMode),
            'socialStats' => $this->buildSocialStats($this->user),
            'softcoreRankMeta' => $softcoreRankMeta,
            'totalHardcoreAchievements' => $this->totalHardcoreAchievements,
            'totalSoftcoreAchievements' => $this->totalSoftcoreAchievements,
            'userClaims' => $this->userClaims,
            'userMassData' => $this->userMassData,
            'username' => $this->user->User,
        ]);
    }

    private function buildRankMetadata(
        string $username = '',
        int $rankType = RankType::Hardcore,
        ?int $predefinedRank = null
    ): array {
        $rank = $predefinedRank ?? getUserRank($username, $rankType);
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

    private function buildDeveloperStats(User $user): array
    {
        $gameAuthoredAchievementsCount = $user->authoredAchievements()
            ->published()
            ->select(DB::raw('COUNT(DISTINCT GameID) as game_count'))
            ->first()
            ->game_count;

        $totalAuthoredLeaderboards = $user->authoredLeaderboards()
            ->select(DB::raw('COUNT(LeaderboardDef.ID) AS TotalAuthoredLeaderboards'))
            ->value('TotalAuthoredLeaderboards');

        $totalAuthoredCodeNotes = $user->authoredCodeNotes()->count();

        $openTickets = null;
        if ($user->ContribCount) {
            $openTickets = array_sum(countOpenTicketsByDev($user->User));
        }

        return compact(
            'gameAuthoredAchievementsCount',
            'openTickets',
            'totalAuthoredCodeNotes',
            'totalAuthoredLeaderboards',
        );
    }

    private function buildSocialStats(User $user): array
    {
        $userSetRequestInformation = getUserRequestsInformation($user);
        $numForumPosts = $user->forumPosts()->count();

        return compact(
            'numForumPosts',
            'userSetRequestInformation',
        );
    }

    private function calculateAverageFinishedGames(array $userJoinedGamesAndAwards): string
    {
        $totalGames = count($userJoinedGamesAndAwards);
        $finishedGames = 0;

        // Iterate over each game to check if it is finished.
        foreach ($userJoinedGamesAndAwards as $game) {
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

    private function calculateAveragePointsPerWeek(string $userMemberSince, int $points = 0): int
    {
        $memberSince = Carbon::createFromFormat('Y-m-d H:i:s', $userMemberSince);
        $now = Carbon::now();

        $weeksOfMembership = $memberSince->diffInWeeks($now);

        // Avoid division by zero.
        if ($weeksOfMembership == 0) {
            return $points;
        }

        $averagePointsPerWeek = $points / $weeksOfMembership;

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
