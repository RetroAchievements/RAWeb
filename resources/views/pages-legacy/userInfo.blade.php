<?php

// TODO migrate to UserController::show() pages/user.blade.php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSorting;
use App\Community\Enums\UserAction;
use App\Enums\Permissions;
use App\Models\User;
use App\Platform\Services\PlayerProgressionService;

$userPage = request('user');
if (empty($userPage) || !isValidUsername($userPage)) {
    abort(404);
}

authenticateFromCookie($user, $permissions, $userDetails);

$maxNumGamesToFetch = requestInputSanitized('g', 5, 'integer');

if ($maxNumGamesToFetch < 1 || $maxNumGamesToFetch > 100) {
    abort(400);
}

$userPageModel = User::firstWhere('User', $userPage);
if (!$userPageModel) {
    abort(404);
}

$userMassData = getUserPageInfo($userPage, numGames: $maxNumGamesToFetch);
if (empty($userMassData)) {
    abort(404);
}

if ((int) $userMassData['Permissions'] < Permissions::Unregistered && $permissions < Permissions::Moderator) {
    abort(404);
}

$userPage = $userMassData['User'];
$userMotto = $userMassData['Motto'];
$userPageID = $userMassData['ID'];
$userSetRequestInformation = getUserRequestsInformation($userPageModel);
$userWallActive = $userMassData['UserWallActive'];
$userIsUntracked = $userMassData['Untracked'];

// Get wall
$numArticleComments = getRecentArticleComments(ArticleType::User, $userPageID, $commentData);

// Get user's feed
// $numFeedItems = getFeed( $userPage, 20, 0, $feedData, 0, 'individual' );

// Calc avg pcts:
$totalPctWon = 0.0;
$numGamesFound = 0;

// Achievement totals
$totalHardcoreAchievements = 0;
$totalSoftcoreAchievements = 0;

$userCompletedGamesList = getUsersCompletedGamesAndMax($userPage);
$userAwards = getUsersSiteAwards($userPageModel);

$playerProgressionService = new PlayerProgressionService();
$userJoinedGamesAndAwards = $playerProgressionService->filterAndJoinGames(
    $userCompletedGamesList,
    $userAwards,
);

$excludedConsoles = ["Hubs", "Events"];

foreach ($userCompletedGamesList as $nextGame) {
    if ($nextGame['PctWon'] > 0) {
        if (!in_array($nextGame['ConsoleName'], $excludedConsoles)) {
            $totalPctWon += $nextGame['PctWon'];
            $numGamesFound++;
            $totalHardcoreAchievements += $nextGame['NumAwardedHC'];
            $totalSoftcoreAchievements += ($nextGame['NumAwarded'] - $nextGame['NumAwardedHC']);
        }
    }
}

$avgPctWon = "0.00";
if ($numGamesFound > 0) {
    $avgPctWon = sprintf("%01.2f", ($totalPctWon / $numGamesFound) * 100.0);
}

sanitize_outputs(
    $userMotto,
    $userPage,
    $userMassData['RichPresenceMsg']
);

$pageTitle = "$userPage";

$daysRecentProgressToShow = 14; // fortnight

$userScoreData = getAwardedList(
    $userPageModel,
    0,
    $daysRecentProgressToShow,
    date("Y-m-d H:i:s", time() - 60 * 60 * 24 * $daysRecentProgressToShow),
    date("Y-m-d H:i:s", time())
);

// Get claim data if the user has jr dev or above permissions
$userClaimData = null;
if (getActiveClaimCount($userPage, true, true) > 0) {
    // Active claims sorted by game title
    $userClaimData = getFilteredClaims(
        claimFilter: ClaimFilters::AllActiveClaims,
        sortType: ClaimSorting::GameAscending,
        username: $userPage
    );
}
?>
<x-app-layout
    :pageTitle="$userPage"
    :pageDescription="$userPage . ' Profile'"
    :pageImage="media_asset('/UserPic/' . $userPage . '.png')"
    pageType="retroachievements:user"
>
    <x-user-profile-meta
        :averageCompletionPercentage="$avgPctWon"
        :totalHardcoreAchievements="$totalHardcoreAchievements"
        :totalSoftcoreAchievements="$totalSoftcoreAchievements"
        :user="$userPageModel"
        :userJoinedGamesAndAwards="$userJoinedGamesAndAwards"
        :userMassData="$userMassData"
        :userClaims="$userClaimData?->toArray()"
    />
    <?php
    $canShowProgressionStatusComponent =
        !empty($userCompletedGamesList)
        // Needs at least one non-event game.
        && count(array_filter($userCompletedGamesList, fn ($game) => $game['ConsoleID'] != 101)) > 0;

    if ($canShowProgressionStatusComponent) {
        echo "<hr class='border-neutral-700 black:border-embed-highlight light:border-embed-highlight my-4' />";

        echo "<div class='mt-1 mb-8 bg-embed p-5 rounded sm:p-2.5 md:p-5 lg:p-3 xl:p-5'>";
        ?>
        <x-user-progression-status
            :userCompletionProgress="$userCompletedGamesList"
            :userJoinedGamesAndAwards="$userJoinedGamesAndAwards"
            :userSiteAwards="$userAwards"
            :userRecentlyPlayed="$userMassData['RecentlyPlayed']"
            :userHardcorePoints="$userMassData['TotalPoints']"
            :userSoftcorePoints="$userMassData['TotalSoftcorePoints']"
        />
        <?php
        echo "</div>";
    }

    echo "<div class='my-8'>";
    ?>
        <x-user-recently-played
            :recentlyPlayedCount="$userMassData['RecentlyPlayedCount'] ?? 0"
            :recentlyPlayedEntities="$userMassData['RecentlyPlayed'] ?? []"
            :recentAchievementEntities="$userMassData['RecentAchievements'] ?? []"
            :recentAwardedEntities="$userMassData['Awarded'] ?? []"
            :targetUsername="$user ?? ''"
            :userAwards="$userAwards"
        />
    <?php
    $recentlyPlayedCount = $userMassData['RecentlyPlayedCount'];
    if ($maxNumGamesToFetch == 5 && $recentlyPlayedCount == 5) {
        echo "<div class='text-right'><a class='btn btn-link' href='/user/$userPage?g=15'>more...</a></div>";
    }
    echo "</div>";

    echo "<div class='commentscomponent left mt-8'>";

    echo "<h2 class='text-h4'>User Wall</h2>";

    if ($userWallActive && request()->user()) {
        // passing 'null' for $user disables the ability to add comments
        RenderCommentsComponent(
            !$userPageModel->isBlocking(request()->user()) ? $user : null,
            $numArticleComments,
            $commentData,
            $userPageID,
            ArticleType::User,
            $permissions
        );
    } else {
        echo "<div>";
        echo "<i>This user has disabled comments.</i>";
        echo "</div>";
    }

    echo "</div>";
    ?>
    <x-slot name="sidebar">
        <?php
        $prefersHiddenUserCompletedSets = request()->cookie('prefers_hidden_user_completed_sets') === 'true';

        RenderSiteAwards($userAwards, $userPage);
        ?>

        @if (count($userCompletedGamesList) >= 1)
            <x-user.completion-progress
                :userJoinedGamesAndAwards="$userJoinedGamesAndAwards"
                :username="$userPage"
            />
        @endif

        <x-user.recent-progress
            :hasAnyPoints="$userMassData['TotalPoints'] > 0 || $userMassData['TotalSoftcorePoints'] > 0"
            :username="$userPage"
            :userScoreData="$userScoreData"
        />

        @if ($user !== null && $user === $userPage)
            <x-user.followed-leaderboard-cta :friendCount="getFriendCount($userPageModel)" />
        @endif
    </x-slot>
</x-app-layout>
