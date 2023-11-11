<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Http\Controller;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\LeaderboardEntryLegacy;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DeveloperRecentActivityController extends Controller
{
    public function __invoke(Request $request): View
    {
        $targetUsername = $request->route()->parameters['user'];
        $foundTargetUser = User::firstWhere('User', $targetUsername);
        if (!$this->getCanViewTargetUser($foundTargetUser)) {
            abort(404);
        }

        $allUserAchievements = collect(getUserAchievementInformation($foundTargetUser->User));
        $allUserAchievementIds = $allUserAchievements->pluck('ID');
        $allUserGameIds = $allUserAchievements->pluck('GameID')->unique();

        $recentUnlocks = $this->fetchRecentUnlocksForDev(
            $allUserAchievementIds,
            shouldUseDateRange: $foundTargetUser->ContribCount > 20000
        );
        $recentAwards = $this->fetchRecentAwardsForDev($allUserGameIds);
        $awardsContributed = $this->fetchAwardsContributedForDev($allUserGameIds);
        $leaderboardEntriesContributed = $this->fetchLeaderboardEntriesContributedForDev($foundTargetUser);
        $recentLeaderboardEntries = $this->fetchRecentLeaderboardEntriesForDev($foundTargetUser);

        return view('platform.developer-recent-activity-page', [
            'awardsContributed' => $awardsContributed,
            'foundTargetUser' => $foundTargetUser,
            'leaderboardEntriesContributed' => $leaderboardEntriesContributed,
            'recentAwards' => $recentAwards->reject(fn ($award) => $award->Untracked),
            'recentLeaderboardEntries' => $recentLeaderboardEntries->reject(fn ($entry) => $entry->Untracked),
            'recentUnlocks' => $recentUnlocks->reject(fn ($unlock) => $unlock->Untracked),
            'targetGameIds' => $allUserGameIds->toArray(),
            'targetUserUnlocksContributed' => $foundTargetUser->ContribCount,
            'targetUserPointsContributed' => $foundTargetUser->ContribYield,
        ]);
    }

    private function attachRecentLeaderboardEntryRowsMetadata(mixed $entryRows): mixed
    {
        // Fetch all the user metadata.
        $userIds = $entryRows->pluck('UserID')->unique();
        $userData = User::whereIn('ID', $userIds)->get(['ID', 'User', 'Untracked'])->keyBy('ID');

        // Fetch all the game metadata.
        $gameIds = $entryRows->pluck('GameID')->unique();
        $gameData = Game::whereIn('ID', $gameIds)->get(['ID', 'Title', 'ImageIcon', 'ConsoleID'])->keyBy('ID');

        // Fetch all the console metadata.
        $consoleIds = $gameData->pluck('ConsoleID')->unique();
        $consoleData = System::whereIn('ID', $consoleIds)->get(['ID', 'Name'])->keyBy('ID');

        // Stitch all the fetched metadata back onto the leaderboard entry rows.
        $entryRows->transform(function ($row) use ($userData, $gameData, $consoleData) {
            $game = $gameData[$row->GameID] ?? null;

            $row->User = $userData[$row->UserID]->User ?? null;
            $row->Untracked = $userData[$row->UserID]->Untracked ?? null;

            $row->GameTitle = $game->Title ?? null;
            $row->GameIcon = $game->ImageIcon ?? null;

            $row->ConsoleName = $consoleData[$game->ConsoleID]->Name ?? null;

            $row->TimestampLabel = $this->buildFriendlyTimestampLabel($row->DateSubmitted, null);

            return $row;
        });

        return $entryRows;
    }

    private function attachRecentAwardRowsMetadata(mixed $awardRows): mixed
    {
        // Fetch all the user metadata.
        $usernames = $awardRows->pluck('User')->unique();
        $userData = User::whereIn('User', $usernames)->get(['ID', 'User', 'Untracked'])->keyBy('User');

        // Fetch all the game metadata.
        $gameIds = $awardRows->pluck('AwardData')->unique();
        $gameData = Game::whereIn('ID', $gameIds)->get(['ID', 'Title', 'ImageIcon', 'ConsoleID'])->keyBy('ID');

        // Fetch all the console metadata.
        $consoleIds = $gameData->pluck('ConsoleID')->unique();
        $consoleData = System::whereIn('ID', $consoleIds)->get(['ID', 'Name'])->keyBy('ID');

        // Stitch all the fetched metadata back onto the award rows.
        $awardRows->transform(function ($row) use ($gameData, $userData, $consoleData) {
            $game = $gameData[$row->AwardData];

            $row->GameTitle = $game->Title ?? null;
            $row->GameIcon = $game->ImageIcon ?? null;

            $row->Untracked = $userData[$row->User]->Untracked ?? null;

            $row->ConsoleName = $consoleData[$game->ConsoleID]->Name ?? null;

            $row->AwardKindLabel = $this->buildAwardKindLabel($row->AwardType, $row->AwardDataExtra);
            $row->TimestampLabel = $this->buildFriendlyTimestampLabel($row->AwardDate, null);

            return $row;
        });

        return $awardRows;
    }

    private function attachRecentUnlockRowsMetadata(mixed $unlockRows): mixed
    {
        // Fetch all the user metadata.
        $userIds = $unlockRows->pluck('user_id')->unique();
        $userData = User::whereIn('ID', $userIds)->get(['ID', 'User', 'Untracked'])->keyBy('ID');

        // Fetch all the achievement metadata.
        $achievementIds = $unlockRows->pluck('achievement_id')->unique();
        $achievementData = Achievement::whereIn('ID', $achievementIds)->get(['ID', 'GameID', 'Title', 'Description', 'BadgeName', 'Points'])->keyBy('ID');

        // Fetch all the game metadata.
        $gameIds = $achievementData->pluck('GameID')->unique();
        $gameData = Game::whereIn('ID', $gameIds)->get(['ID', 'Title', 'ImageIcon', 'ConsoleID'])->keyBy('ID');

        // Fetch all the console metadata.
        $consoleIds = $gameData->pluck('ConsoleID')->unique();
        $consoleData = System::whereIn('ID', $consoleIds)->get(['ID', 'Name'])->keyBy('ID');

        // Stitch all the fetched metadata back onto the unlock rows.
        $unlockRows->transform(function ($row) use ($userData, $gameData, $achievementData, $consoleData) {
            $achievement = $achievementData[$row->achievement_id];
            $game = $gameData[$achievement->GameID] ?? null;

            $row->User = $userData[$row->user_id]->User ?? null;
            $row->Untracked = $userData[$row->user_id]->Untracked ?? null;

            $row->Title = $achievement->Title ?? null;
            $row->Description = $achievement->Description ?? null;
            $row->BadgeName = $achievement->BadgeName ?? null;
            $row->Points = $achievement->Points ?? null;

            $row->GameID = $achievement->GameID;
            $row->GameTitle = $game->Title ?? null;
            $row->GameIcon = $game->ImageIcon ?? null;

            $row->ConsoleName = $consoleData[$game->ConsoleID]->Name ?? null;

            $row->TimestampLabel = $this->buildFriendlyTimestampLabel($row->unlocked_at, $row->unlocked_hardcore_at);

            return $row;
        });

        return $unlockRows;
    }

    private function buildAwardKindLabel(int $awardType, int $awardDataExtra): string
    {
        if ($awardType == AwardType::GameBeaten) {
            if ($awardDataExtra === UnlockMode::Hardcore) {
                return 'Beaten';
            }

            return 'Beaten (softcore)';
        } elseif ($awardType == AwardType::Mastery) {
            if ($awardDataExtra === UnlockMode::Hardcore) {
                return 'Mastered';
            }

            return 'Completed';
        }

        return 'Other';
    }

    private function buildAwardsForDevBaseQuery(mixed $allUserGameIds): mixed
    {
        return PlayerBadge::from('SiteAwards as pb')
            ->whereIn('pb.AwardData', $allUserGameIds)
            ->whereIn('pb.AwardType', [1, 8])
            ->joinSub(
                // If a user has both the softcore and hardcore award for the same
                // AwardType and same game, only consider the most prestigious award.
                PlayerBadge::selectRaw('MAX(AwardDataExtra) as MaxExtra, AwardData, AwardType, User')
                    ->groupBy('AwardData', 'AwardType', 'User'),
                'priority_awards',
                function ($join) {
                    $join->on('pb.AwardData', '=', 'priority_awards.AwardData')
                        ->on('pb.AwardType', '=', 'priority_awards.AwardType')
                        ->on('pb.User', '=', 'priority_awards.User')
                        ->on('pb.AwardDataExtra', '=', 'priority_awards.MaxExtra');
                }
            );
    }

    private function buildFriendlyTimestampLabel(?Carbon $unlockedAt, ?Carbon $unlockedHardcoreAt): string
    {
        // Use the hardcore unlock date if available, otherwise use the normal unlock date.
        $timestamp = $unlockedHardcoreAt ?? $unlockedAt;

        // If there is no timestamp, return an empty string.
        if (!$timestamp) {
            return '';
        }

        // Compare the date with now.
        if ($timestamp->isToday()) {
            // If the timestamp is from today, use the 'timeago' format.
            return $timestamp->diffForHumans();
        } elseif ($timestamp->isYesterday()) {
            // If the timestamp is from yesterday, use the 'Yesterday at' format.
            return 'Yesterday at ' . $timestamp->format('g:ia');
        } else {
            // If the timestamp is older, use the 'F j Y, g:ia' format.
            return $timestamp->format('F j Y, g:ia');
        }
    }

    private function buildLeaderboardEntriesForDevBaseQuery(User $targetUser): mixed
    {
        return LeaderboardEntryLegacy::query()
            ->join('LeaderboardDef', 'LeaderboardDef.ID', '=', 'LeaderboardEntry.LeaderboardID')
            ->where('LeaderboardDef.Author', $targetUser->User);
    }

    private function fetchAwardsContributedForDev(mixed $allUserGameIds): int
    {
        return $this->buildAwardsForDevBaseQuery($allUserGameIds)->count();
    }

    private function fetchLeaderboardEntriesContributedForDev(User $targetUser): int
    {
        return $this->buildLeaderboardEntriesForDevBaseQuery($targetUser)->count();
    }

    private function fetchRecentAwardsForDev(mixed $allUserGameIds, int $limit = 50): mixed
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $mostRecentAwards = $this->buildAwardsForDevBaseQuery($allUserGameIds)
            ->whereDate('pb.AwardDate', '>=', $thirtyDaysAgo)
            ->orderByDesc('pb.AwardDate')
            ->take($limit)
            ->get();

        return $this->attachRecentAwardRowsMetadata($mostRecentAwards);
    }

    private function fetchRecentLeaderboardEntriesForDev(User $targetUser, int $limit = 200): mixed
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $mostRecentLeaderboardEntries = $this->buildLeaderboardEntriesForDevBaseQuery($targetUser)
            ->whereDate('LeaderboardEntry.DateSubmitted', '>=', $thirtyDaysAgo)
            ->orderByDesc('LeaderboardEntry.DateSubmitted')
            ->take($limit)
            ->get([
                'LeaderboardDef.ID', 'LeaderboardDef.GameID', 'LeaderboardDef.Format', 'LeaderboardDef.Title', 'LeaderboardDef.Description',
                'LeaderboardEntry.LeaderboardID', 'LeaderboardEntry.UserID', 'LeaderboardEntry.Score', 'LeaderboardEntry.DateSubmitted',
            ]);

        return $this->attachRecentLeaderboardEntryRowsMetadata($mostRecentLeaderboardEntries);
    }

    private function fetchRecentUnlocksForDev(mixed $allUserAchievementIds, int $limit = 200, bool $shouldUseDateRange = false): mixed
    {
        $query = PlayerAchievement::whereIn('achievement_id', $allUserAchievementIds)
            ->orderByDesc('unlocked_at');

        if ($shouldUseDateRange) {
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $query->whereDate('unlocked_at', '>=', $thirtyDaysAgo);
        }

        $mostRecentUnlocks = $query->take($limit)->get();

        return $this->attachRecentUnlockRowsMetadata($mostRecentUnlocks);
    }

    private function getCanViewTargetUser(?User $user): bool
    {
        $targetUserPermissions = (int) $user->getAttribute('Permissions');

        return (bool) $user && $targetUserPermissions >= Permissions::Registered;
    }
}
