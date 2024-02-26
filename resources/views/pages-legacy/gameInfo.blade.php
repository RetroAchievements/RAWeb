<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\UserGameListEntry;
use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Models\User;
use App\Platform\Controllers\CompareUnlocksController;
use App\Platform\Controllers\RelatedGamesTableController;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\ImageType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Services\GameListService;

$gameID = (int) request('game');
if (empty($gameID)) {
    abort(404);
}

authenticateFromCookie($user, $permissions, $userDetails);

$userID = $userDetails['ID'] ?? 0;
$userWebsitePrefs = $userDetails['websitePrefs'] ?? null;
$matureContentPref = UserPreference::Site_SuppressMatureContentWarning;

$officialFlag = AchievementFlag::OfficialCore;
$unofficialFlag = AchievementFlag::Unofficial;
$flagParam = requestInputSanitized('f', $officialFlag, 'integer');
$isOfficial = false;
if ($flagParam !== $unofficialFlag) {
    $isOfficial = true;
    $flagParam = $officialFlag;
}

$userModel = null;
$defaultSort = 1;
if (isset($user)) {
    $userModel = User::firstWhere('User', $user);
    $defaultSort = 13;
}
$sortBy = requestInputSanitized('s', $defaultSort, 'integer');

if (!isset($user) && ($sortBy == 3 || $sortBy == 13)) {
    $sortBy = 1;
}

$numAchievements = getGameMetadata($gameID, $userModel, $achievementData, $gameData, $sortBy, null, $flagParam, metrics: true);

if (empty($gameData)) {
    abort(404);
}

$gameTitle = $gameData['Title'];
$consoleName = $gameData['ConsoleName'];
$consoleID = $gameData['ConsoleID'];
$forumTopicID = $gameData['ForumTopicID'];
$richPresenceData = $gameData['RichPresencePatch'];
$guideURL = $gameData['GuideURL'];

// Entries that aren't actual game only have alternatives exposed, e.g. hubs.
$isFullyFeaturedGame = $consoleName !== 'Hubs';
$isEventGame = $consoleName == 'Events';

$pageTitle = "$gameTitle ($consoleName)";

$unlockedAchievements = array_filter($achievementData, function ($achievement) {
    return !empty($achievement['DateEarned']) || !empty($achievement['DateEarnedHardcore']);
});
$beatenGameCreditDialogContext = buildBeatenGameCreditDialogContext($unlockedAchievements);

$relatedGames = $isFullyFeaturedGame ? getGameAlternatives($gameID) : getGameAlternatives($gameID, $sortBy);
$gameAlts = [];
$gameHubs = [];
$gameEvents = [];
$gameSubsets = [];
$subsetPrefix = $gameData['Title'] . " [Subset - ";
foreach ($relatedGames as $gameAlt) {
    if ($gameAlt['ConsoleName'] == 'Hubs') {
        $gameHubs[] = $gameAlt;
    } else {
        if ($gameAlt['ConsoleName'] == 'Events') {
            $gameEvents[] = $gameAlt;
        }

        if (str_starts_with($gameAlt['Title'], $subsetPrefix)) {
            $gameSubsets[] = $gameAlt;
        } else {
            $gameAlts[] = $gameAlt;
        }
    }
}

$v = requestInputSanitized('v', 0, 'integer');
$gate = false;
if ($v != 1) {
    if ($isFullyFeaturedGame) {
        foreach ($gameHubs as $hub) {
            if ($hub['Title'] == '[Theme - Mature]') {
                if ($userDetails && BitSet($userDetails['websitePrefs'], $matureContentPref)) {
                    break;
                }
                $gate = true;
            }
        }
    } elseif (str_contains($gameTitle, '[Theme - Mature]')) {
        $gate = !$userDetails || !BitSet($userDetails['websitePrefs'], $matureContentPref);
    }
}
?>

<?php
$achDist = null;
$achDistHardcore = null;
$authorInfo = [];
$commentData = null;
$gameTopAchievers = null;
$lbData = null;
$numArticleComments = null;
$numDistinctPlayers = null;
$numEarnedCasual = null;
$numEarnedHardcore = null;
$numLeaderboards = null;
$screenshotMaxHeight = null;
$screenshotWidth = null;
$totalEarnedCasual = null;
$totalEarnedHardcore = null;
$totalEarnedTrueRatio = null;
$totalPossible = null;
$totalPossibleTrueRatio = null;
$isSoleAuthor = false;
$claimData = null;
$isGameBeatable = false;
$isBeatenHardcore = false;
$isBeatenSoftcore = false;
$hasBeatenHardcoreAward = false;
$hasBeatenSoftcoreAward = false;
$userGameProgressionAwards = [
    'beaten-softcore' => null,
    'beaten-hardcore' => null,
    'completed' => null,
    'mastered' => null,
];

if ($isFullyFeaturedGame) {
    $numDistinctPlayers = $gameData['NumDistinctPlayers'];

    $achDist = getAchievementDistribution($gameID, UnlockMode::Softcore, $user, $flagParam, $numDistinctPlayers);
    $achDistHardcore = getAchievementDistribution($gameID, UnlockMode::Hardcore, $user, $flagParam, $numDistinctPlayers);

    $numArticleComments = getRecentArticleComments(ArticleType::Game, $gameID, $commentData);

    $numLeaderboards = getLeaderboardsForGame($gameID, $lbData, $user, retrieveHidden: false);

    if (isset($user)) {
        // Determine if the logged in user is the sole author of the set
        $isSoleAuthor = checkIfSoleDeveloper($user, $gameID);

        // Determine if the logged in user has any progression awards for this set
        $userGameProgressionAwards = getUserGameProgressionAwards($gameID, $user);
        $hasBeatenSoftcoreAward = !is_null($userGameProgressionAwards['beaten-hardcore']);
        $hasBeatenHardcoreAward = !is_null($userGameProgressionAwards['beaten-softcore']);
    }

    $screenshotWidth = 200;
    $screenshotMaxHeight = 240; // corresponds to the DS screen aspect ratio

    // Quickly calculate earned/potential
    $totalEarnedCasual = 0;
    $totalEarnedHardcore = 0;
    $numEarnedCasual = 0;
    $numEarnedHardcore = 0;
    $totalPossible = 0;

    // Quickly calculate the player's beaten status on an optimistic basis
    $totalProgressionAchievements = 0;
    $totalWinConditionAchievements = 0;
    $totalEarnedProgression = 0;
    $totalEarnedProgressionHardcore = 0;
    $totalEarnedWinCondition = 0;
    $totalEarnedWinConditionHardcore = 0;

    $totalEarnedTrueRatio = 0;
    $totalPossibleTrueRatio = 0;

    $authorName = [];
    $authorCount = [];
    if (isset($achievementData)) {
        foreach ($achievementData as &$nextAch) {
            $lowercasedAuthor = mb_strtolower($nextAch['Author']);

            // Add author to array if it's not already there and initialize achievement count for that author.
            if (!in_array($nextAch['Author'], $authorName)) {
                $authorName[$lowercasedAuthor] = $nextAch['Author'];
                $authorCount[$lowercasedAuthor] = 1;
            } // If author is already in array then increment the achievement count for that author.
            else {
                $authorCount[$lowercasedAuthor]++;
            }

            $totalPossible += $nextAch['Points'];
            $totalPossibleTrueRatio += $nextAch['TrueRatio'];

            // Tally up how many Progression and Win Condition achievements the user has earned.
            // We'll use this to determine if they're potentially missing a beaten game award.
            if (
                $user
                && isset($nextAch['type'])
                && ($nextAch['type'] == AchievementType::Progression || $nextAch['type'] == AchievementType::WinCondition)
            ) {
                $isGameBeatable = true;

                if ($nextAch['type'] == AchievementType::Progression) {
                    $totalProgressionAchievements++;
                    if (isset($nextAch['DateEarned'])) {
                        $totalEarnedProgression++;
                    }
                    if (isset($nextAch['DateEarnedHardcore'])) {
                        $totalEarnedProgressionHardcore++;
                    }
                } elseif ($nextAch['type'] == AchievementType::WinCondition) {
                    $totalWinConditionAchievements++;
                    if (isset($nextAch['DateEarned'])) {
                        $totalEarnedWinCondition++;
                    }
                    if (isset($nextAch['DateEarnedHardcore'])) {
                        $totalEarnedWinConditionHardcore++;
                    }
                }
            }

            if (isset($nextAch['DateEarnedHardcore'])) {
                $numEarnedHardcore++;
                $totalEarnedHardcore += $nextAch['Points'];
                $totalEarnedTrueRatio += $nextAch['TrueRatio'];
            } elseif (isset($nextAch['DateEarned'])) {
                $numEarnedCasual++;
                $totalEarnedCasual += $nextAch['Points'];
            }
        }
        // Combine arrays and sort by achievement count.
        $authorInfo = array_combine($authorName, $authorCount);
        array_multisort($authorCount, SORT_DESC, $authorInfo);
    }

    // Show the beaten award display in the progress component optimistically.
    // The actual award metadata is updated async via actions/background jobs.
    if ($isGameBeatable) {
        $neededProgressions = $totalProgressionAchievements > 0 ? $totalProgressionAchievements : 0;
        $neededWinConditions = $totalWinConditionAchievements > 0 ? 1 : 0;

        $isBeatenSoftcore = (
            $totalEarnedProgression === $totalProgressionAchievements
            && $totalEarnedWinCondition >= $neededWinConditions
        );

        $isBeatenHardcore = (
            $totalEarnedProgressionHardcore === $totalProgressionAchievements
            && $totalEarnedWinConditionHardcore >= $neededWinConditions
        );
    }

    // Get the top ten players at this game:
    $gameTopAchievers = getGameTopAchievers($gameID);

    $claimData = getClaimData($gameID, true);
}

sanitize_outputs(
    $gameTitle,
    $consoleName,
    $richPresenceData,
    $user,
);
?>

<?php if ($isFullyFeaturedGame): ?>
    <?php
        $pageType = 'retroachievements:game';
        $pageImage = media_asset($gameData['ImageIcon']);
        $pageDescription = generateGameMetaDescription(
            $gameTitle,
            $consoleName,
            $numAchievements,
            $totalPossible,
            $isEventGame
        );
    ?>
<?php endif ?>

@if ($gate)
    <x-app-layout
        :pageTitle="$pageTitle"
    >
        <x-game.mature-content-gate
            :gameId="$gameID"
            :gameTitle="$gameTitle"
            :consoleId="$consoleID"
            :consoleName="$consoleName"
            :userWebsitePrefs="$userDetails['websitePrefs'] ?? 0"
        />
    </x-app-layout>
    <?php return ?>
@endif

<x-app-layout
    :pageTitle="$pageTitle"
    :pageDescription="$pageDescription ?? null"
    :pageImage="$pageImage ?? null"
    :pageType="$pageType ?? null"
>
<?php if ($isFullyFeaturedGame): ?>
    <?php if ($numDistinctPlayers): ?>
        <script defer src="https://www.gstatic.com/charts/loader.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof google !== 'undefined') {
                google.load('visualization', '1.0', { 'packages': ['corechart'] });
                google.setOnLoadCallback(drawCharts);
            }
        });

        function drawCharts() {
            var dataTotalScore = new google.visualization.DataTable();

            // Declare columns
            dataTotalScore.addColumn('number', 'Total Achievements Won');
            dataTotalScore.addColumn('number', 'Hardcore Users');
            dataTotalScore.addColumn('number', 'Softcore Users');

            dataTotalScore.addRows([
                <?php
                [$buckets, $isDynamicBucketingEnabled] = generateEmptyBucketsWithBounds($numAchievements);
                [$largestWonByCount] = calculateBuckets($buckets, $isDynamicBucketingEnabled, $numAchievements, $achDist, $achDistHardcore);
                $allAchievementsCount = handleAllAchievementsCase($numAchievements, $achDist, $achDistHardcore, $buckets);
                $largestWonByCount = max($allAchievementsCount, $largestWonByCount);

                $numGridlines = ($numAchievements < 20) ? $numAchievements : 10;
                if ($largestWonByCount > 20) {
                    $largestWonByCount = -2;
                }

                $hAxisValues = generateBucketLabelsAndValues($numAchievements, $buckets);
                ?>
            ]);
            var hAxisValues = <?php echo json_encode($hAxisValues); ?>;
            var optionsTotalScore = {
                isStacked: true,
                backgroundColor: 'transparent',
                titleTextStyle: { color: '#186DEE' },
                hAxis: {
                    textStyle: { color: '#186DEE' },
                    gridlines: {
                        count: <?= $numGridlines ?>,
                        color: '#333333'
                    },
                    <?php
                    if ($isDynamicBucketingEnabled) {
                        echo 'ticks: hAxisValues.map(function(value, index) { return {v: index + 1, f: value.toString()}; }),';
                    }
                    ?>
                    minorGridlines: { count: 0 },
                    format: '#',
                    slantedTextAngle: 90,
                    maxAlternation: 0,
                },
                vAxis: {
                    textStyle: { color: '#186DEE' },
                    gridlines: {
                        count: <?= $largestWonByCount + 1 ?>,
                        color: '#333333'
                    },
                    minorGridlines: { color: '#333333' },
                    viewWindow: { min: 0 },
                    format: '#'
                },
                legend: { position: 'none' },
                chartArea: {
                    'width': '80%',
                    'height': '78%'
                },
                height: 260,
                colors: ['#cc9900', '#737373'],
                pointSize: 4,
            };

            function resize() {
                chartScoreProgress = new google.visualization.ColumnChart(document.getElementById('chart_distribution'));
                chartScoreProgress.draw(dataTotalScore, optionsTotalScore);
                // google.visualization.events.addListener(chartScoreProgress, 'select', selectHandlerScoreProgress );
            }

            window.onload = resize();
            window.onresize = resize;
        }
        </script>
    <?php endif ?>
<?php endif ?>
    <div id="achievement">
        <?php

        if ($isFullyFeaturedGame) {
            echo "<div class='navpath'>";
            echo renderGameBreadcrumb($gameData, addLinkToLastCrumb: $flagParam === $unofficialFlag);
            if ($flagParam === $unofficialFlag) {
                echo " &raquo; <b>Unofficial Achievements</b>";
            }
            echo "</div>";
        }

        $escapedGameTitle = attributeEscape($gameTitle);
        $consoleName = $gameData['ConsoleName'] ?? null;
        $developer = $gameData['Developer'] ?? null;
        $publisher = $gameData['Publisher'] ?? null;
        $genre = $gameData['Genre'] ?? null;
        $released = $gameData['Released'] ?? null;
        $imageIcon = media_asset($gameData['ImageIcon']);
        $imageTitle = media_asset($gameData['ImageTitle']);
        $imageIngame = media_asset($gameData['ImageIngame']);
        $pageTitleAttr = attributeEscape($pageTitle);

        $systemIconUrl = getSystemIconUrl($consoleID);

        $numMissableAchievements = count(
            array_filter(
                $achievementData,
                fn ($achievement) => $achievement['type'] === AchievementType::Missable
            ));

        $gameMetaBindings = [
            'claimData' => $claimData,
            'consoleID' => $consoleID,
            'consoleName' => $consoleName,
            'developer' => $developer,
            'forumTopicID' => $forumTopicID,
            'gameHubs' => $gameHubs,
            'gameID' => $gameID,
            'gameTitle' => $gameTitle,
            'genre' => $genre,
            'iconUrl' => $systemIconUrl,
            'imageIcon' => $imageIcon,
            'isFullyFeaturedGame' => $isFullyFeaturedGame,
            'isOfficial' => $isOfficial,
            'isSoleAuthor' => $isSoleAuthor,
            'numAchievements' => $numAchievements,
            'numMissableAchievements' => $numMissableAchievements,
            'permissions' => $permissions,
            'publisher' => $publisher,
            'released' => $released,
            'totalPossible' => $totalPossible,
            'totalPossibleTrueRatio' => $totalPossibleTrueRatio,
            'user' => $user,
            'userModel' => $userModel,
        ];
        ?>
            <x-game.heading
                :gameId="$gameID"
                :gameTitle="$gameTitle"
                :consoleId="$consoleID"
                :consoleName="$consoleName"
                :user="$user"
                :userPermissions="$permissions"
            />
            <x-game.primary-meta
                :developer="$developer"
                :publisher="$publisher"
                :genre="$genre"
                :released="$released"
                :imageIcon="$imageIcon"
                :metaKind="$isFullyFeaturedGame ? 'Game' : 'Hub'"
            >
                @if ($isFullyFeaturedGame)
                    <x-game.primary-meta-row-item label="Developer" :metadataValue="$developer" :gameHubs="$gameHubs" :altLabels="['Hacker']" />
                    <x-game.primary-meta-row-item label="Publisher" :metadataValue="$publisher" :gameHubs="$gameHubs" :altLabels="['Hacks']" />
                    <x-game.primary-meta-row-item label="Genre" :metadataValue="$genre" :gameHubs="$gameHubs" :altLabels="['Subgenre']" />
                @else
                    <x-game.primary-meta-row-item label="Developer" :metadataValue="$developer" />
                    <x-game.primary-meta-row-item label="Publisher" :metadataValue="$publisher" />
                    <x-game.primary-meta-row-item label="Genre" :metadataValue="$genre" />
                @endif
                <x-game.primary-meta-row-item label="Released" :metadataValue="$released" />
            </x-game.primary-meta>

        @if ($isFullyFeaturedGame)
            <x-game.screenshots :titleImageSrc="$imageTitle" :ingameImageSrc="$imageIngame" />
        @endif

        <?php
        // Display dev section if logged in as either a developer or a jr. developer viewing a non-hub page
        if (isset($user) && ($permissions >= Permissions::Developer || ($isFullyFeaturedGame && $permissions >= Permissions::JuniorDeveloper))) {
            $hasMinimumDeveloperPermissions = $permissions >= Permissions::Developer || (($isSoleAuthor || hasSetClaimed($user, $gameID, true, ClaimSetType::NewSet)) && $permissions >= Permissions::JuniorDeveloper);
            echo "<div class='devbox mb-3'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev ▼</span>";
            echo "<div id='devboxcontent' style='display: none'>";
            // Display the option to switch between viewing core/unofficial for non-hub page
            if ($isFullyFeaturedGame) {
                echo "<div class='lg:flex justify-between gap-5 mb-5'>";
                echo "<div class='grow'>";

                if ($flagParam == $unofficialFlag) {
                    echo "<div><a class='btn btn-link' href='/game/$gameID" . ($v == 1 ? '?v=1' : '') . "'>View Core Achievements</a></div>";
                    echo "<div><a class='btn btn-link' href='/achievementinspector.php?g=$gameID&f=5'>Manage Unofficial Achievements</a></div>";
                } else {
                    echo "<div><a class='btn btn-link' href='/game/$gameID?f=5" . ($v == 1 ? '&v=1' : '') . "'>View Unofficial Achievements</a></div>";
                    echo "<div><a class='btn btn-link' href='/achievementinspector.php?g=$gameID'>Manage Core Achievements</a></div>";
                }

                // Display leaderboard management options depending on the current number of leaderboards
                if ($numLeaderboards != 0) {
                    echo "<div><a class='btn btn-link' href='/leaderboardList.php?g=$gameID'>Manage Leaderboards</a></div>";
                }

                if ($permissions >= Permissions::Developer) {
                    echo "<div><a class='btn btn-link' href='/game/$gameID/hashes/manage'>Manage Hashes</a></div>";
                }

                $primaryClaimUser = null;
                foreach ($claimData as $claim) {
                    if ($claimData[0]['ClaimType'] == ClaimType::Primary) {
                        $primaryClaimUser = $claimData[0]['User'];
                        break;
                    }
                }
                if ($permissions >= Permissions::Moderator || $primaryClaimUser === $user) {
                    $interestedUsers = UserGameListEntry::where('type', UserGameListType::Develop)
                        ->where('GameID', $gameID)
                        ->count();
                    echo "<div><a class='btn btn-link' href='" . route('game.dev-interest', $gameID) . "'>View Developer Interest ($interestedUsers)</a></div>";
                }

                if ($permissions >= Permissions::Moderator && !$isEventGame) {
                    echo "<div><a class='btn btn-link' href='/manageclaims.php?g=$gameID'>Manage Claims</a></div>";
                }

                echo "</div>";
                // right column
                echo "<div class='grow'>";

                RenderUpdateSubscriptionForm(
                    "updateachievementssub",
                    SubscriptionSubjectType::GameAchievements,
                    $gameID,
                    isUserSubscribedTo(SubscriptionSubjectType::GameAchievements, $gameID, $userID),
                    'Achievement Comments'
                );

                RenderUpdateSubscriptionForm(
                    "updateticketssub",
                    SubscriptionSubjectType::GameTickets,
                    $gameID,
                    isUserSubscribedTo(SubscriptionSubjectType::GameTickets, $gameID, $userID),
                    'Tickets'
                );
                ?>
                {{-- Display the claims links if not an event game --}}
                @if (!$isEventGame)
                    @if ($permissions >= Permissions::Developer)
                            <x-game.add-to-list
                                :gameId="$gameID"
                                :type="UserGameListType::Develop"
                                :user="$user"
                            />
                    @endif
                    <x-game.devbox-claim-management
                        :claimData="$claimData"
                        :consoleId="$consoleID"
                        :forumTopicId="$forumTopicID"
                        :gameId="$gameID"
                        :gameTitle="$gameTitle"
                        :isOfficial="$isOfficial"
                        :isSoleAuthor="$isSoleAuthor"
                        :numAchievements="$numAchievements"
                        :user="$user"
                        :userPermissions="$permissions"
                    />
                @endif
                <?php
                echo "</div>"; // end right column
                echo "</div>";
            }

            if ($hasMinimumDeveloperPermissions) {
                // Only allow developers to rename a game
                if ($permissions >= Permissions::Developer) {
                    echo "<form class='mb-2' method='post' action='/request/game/update-title.php'>";
                    echo csrf_field();
                    echo "<input type='hidden' name='game' value='$gameID' />";
                    echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";
                    echo "<label for='game_title'>Name</label>";
                    echo "<input type='text' name='title' id='game_title' value='$escapedGameTitle' maxlength='80' class='w-full'>";
                    echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                    echo "</div>";
                    echo "</form>";
                }

                echo "<form class='mb-2' method='post' action='/request/game/update-meta.php'>";
                echo csrf_field();
                echo "<input type='hidden' name='game' value='$gameID'>";
                echo "<input type='hidden' name='guide_url' value='" . attributeEscape($guideURL) . "'>";
                echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";
                echo "<label for='game_developer'>Developer</label><input type='text' name='developer' id='game_developer' value='" . attributeEscape($developer) . "' class='w-full'>";
                echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                echo "<label for='game_publisher'>Publisher</label><input type='text' name='publisher' id='game_publisher' value='" . attributeEscape($publisher) . "' class='w-full'>";
                echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                echo "<label for='game_genre'>Genre</label><input type='text' name='genre' id='game_genre' value='" . attributeEscape($genre) . "' class='w-full'>";
                echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                echo "<label for='game_release'>First Released</label><input type='text' name='release' id='game_release' value='" . attributeEscape($released) . "' class='w-full'>";
                echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                echo "</div>";
                echo "</form>";

                if ($isFullyFeaturedGame) {
                    echo "<form class='mb-2' method='post' action='/request/game/update-image.php' enctype='multipart/form-data'>";
                    echo csrf_field();
                    echo "<input type='hidden' name='game' value='$gameID'>";
                    echo "<input type='hidden' name='type' value='" . ImageType::GameTitle . "'>";
                    echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";
                    echo "<label for='image_" . ImageType::GameTitle . "'>Title Screenshot</label>";
                    echo "<input type='file' name='file' id='image_" . ImageType::GameTitle . "' class='w-full'>";
                    echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                    echo "</div>";
                    echo "</form>";

                    echo "<form class='mb-2' method='post' action='/request/game/update-image.php' enctype='multipart/form-data'>";
                    echo csrf_field();
                    echo "<input type='hidden' name='game' value='$gameID'>";
                    echo "<input type='hidden' name='type' value='" . ImageType::GameInGame . "'>";
                    echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";
                    echo "<label for='image_" . ImageType::GameInGame . "'>In-game Screenshot</label>";
                    echo "<input type='file' name='file' id='image_" . ImageType::GameInGame . "' class='w-full'>";
                    echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                    echo "</div>";
                    echo "</form>";
                }

                echo "<form class='mb-2' method='post' action='/request/game/update-image.php' enctype='multipart/form-data'>";
                echo csrf_field();
                echo "<input type='hidden' name='game' value='$gameID'>";
                echo "<input type='hidden' name='type' value='" . ImageType::GameIcon . "'>";
                echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";
                echo "<label for='image_" . ImageType::GameIcon . "'>Icon</label>";
                echo "<input type='file' name='file' id='image_" . ImageType::GameIcon . "' class='w-full'>";
                echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                echo "</div>";
                echo "</form>";

                if ($isFullyFeaturedGame) {
                    echo "<form class='mb-2' method='post' action='/request/game/update-image.php' enctype='multipart/form-data'>";
                    echo csrf_field();
                    echo "<input type='hidden' name='game' value='$gameID'>";
                    echo "<input type='hidden' name='type' value='" . ImageType::GameBoxArt . "'>";
                    echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";
                    echo "<label for='image_" . ImageType::GameBoxArt . "'>Box Art</label>";
                    echo "<input type='file' name='file' id='image_" . ImageType::GameBoxArt . "' class='w-full'>";
                    echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                    echo "</div>";
                    echo "</form>";
                }
            }

            if ($permissions >= Permissions::Moderator) {
                echo "<form class='mb-2' method='post' action='/request/game/update-forum-topic.php'>";
                echo csrf_field();
                echo "<input type='hidden' name='game' value='$gameID'>";
                echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";
                echo "<label for='game_forum_topic'>New Forum Topic ID</label>";
                echo "<input type='text' name='forum_topic' id='game_forum_topic' class='w-full'>";
                echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                echo "</div>";
                echo "</form>";
            }

            if ($hasMinimumDeveloperPermissions) {
                echo "<form class='mb-2' method='post' action='/request/game/update-meta.php'>";
                echo csrf_field();
                echo "<input type='hidden' name='game' value='$gameID'>";
                echo "<input type='hidden' name='developer' value='" . attributeEscape($developer) . "'>";
                echo "<input type='hidden' name='publisher' value='" . attributeEscape($publisher) . "'>";
                echo "<input type='hidden' name='genre' value='" . attributeEscape($genre) . "'>";
                echo "<input type='hidden' name='release' value='" . attributeEscape($released) . "'>";
                echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";

                $guideUrlHelperContent = "Must be from https://github.com/RetroAchievements/guides";
                echo "<label for='guide_url' class='cursor-help flex items-center gap-x-1' title='$guideUrlHelperContent' aria-label='Guide URL, $guideUrlHelperContent'>";
                echo "Guide URL";
                ?>
                <x-fas-info-circle class="w-5 h-5" aria-hidden="true" />
                <?php
                echo "</label>";

                echo "<input type='url' name='guide_url' id='guide_url' value='" . attributeEscape($guideURL) . "' class='w-full'>";
                echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                echo "</div>";
                echo "</form>";
            }

            if ($permissions >= Permissions::Developer) {
                echo "<form class='mb-2' method='post' action='/request/game-relation/create.php'>";
                echo csrf_field();
                echo "<input type='hidden' name='game' value='$gameID'>";
                echo "<div class='md:grid grid-cols-[180px_1fr_100px] gap-1 items-center mb-1'>";
                echo "<label for='game_relation_add'>Add Related Games<br>(CSV of game IDs)</label>";
                echo "<input type='text' name='relations' id='game_relation_add' class='w-full'>";
                echo "<div class='text-right'><button class='btn'>Add</button></div>";
                echo "</div>";
                echo "</form>";

                if (!empty($relatedGames)) {
                    echo "<form class='mb-2' method='post' action='/request/game-relation/delete.php'>";
                    echo csrf_field();
                    echo "<input type='hidden' name='game' value='$gameID'>";
                    echo "<div><label for='game_relations'>Related Games</label></div>";
                    echo "<select class='resize-y w-full overflow-auto h-[125px] mb-1' name='relations[]' id='game_relations' multiple>";
                    foreach ($relatedGames as $gameAlt) {
                        $gameAltID = $gameAlt['gameIDAlt'];
                        $gameAltTitle = $gameAlt['Title'];
                        $gameAltConsole = $gameAlt['ConsoleName'];
                        sanitize_outputs(
                            $gameAltTitle,
                            $gameAltConsole,
                        );
                        echo "<option value='$gameAltID'>$gameAltTitle ($gameAltConsole)</option>";
                    }
                    echo "</select>";
                    echo "<div class='text-right'><button class='btn btn-danger' onclick='return confirm(\"Are you sure you want to remove the selected relations?\")'>Remove</button></div>";
                    echo "</form>";
                }
            }
            if ($isFullyFeaturedGame) {
                echo "<div><label for='game_rich_presence'><a href='https://docs.retroachievements.org/Rich-Presence/'>Rich Presence</a> Script</label></div>";
                if ($hasMinimumDeveloperPermissions) {
                    echo "<form class='mb-2' method='post' action='/request/game/update-rich-presence.php'>";
                    echo csrf_field();
                    echo "<input type='hidden' value='$gameID' name='game'>";
                    echo "<textarea class='code w-full h-[320px] mb-1' name='rich_presence' id='game_rich_presence' maxlength='60000'>$richPresenceData</textarea><br>";
                    echo "<div class='text-right'><button class='btn'>Submit</button></div>";
                    echo "</form>";
                } else {
                    echo "<textarea class='code w-full h-[320px] mb-2' id='game_rich_presence' readonly>$richPresenceData</textarea>";
                }
            }

            $numModificationComments = getRecentArticleComments(ArticleType::GameModification, $gameID, $modificationCommentData);
            RenderCommentsComponent(null, $numModificationComments, $modificationCommentData, $gameID, ArticleType::GameModification, $permissions);

            echo "</div>"; // devboxcontent
            echo "</div>"; // devbox
        }

        if ($isFullyFeaturedGame) {
            echo "<div class='md:float-right mb-4 md:mb-0'>";

            // Only show set request option for logged in users, games without achievements, and core achievement page
            if ($user !== null && $numAchievements == 0 && $flagParam == $officialFlag) {
                ?>
                    <x-game.set-requests
                        :gameId="$gameID"
                        :user="$userModel"
                    />
                <?php
            }

            echo "</div>";

            if ($flagParam == $unofficialFlag) {
                echo "<h2 class='text-h4'><b>Unofficial</b> Achievements</h2>";
                echo "<a href='/game/$gameID'><b>Click here to view the Core Achievements</b></a><br>";
            } else {
                echo "<h2 class='text-h4'>Achievements</h2>";
            }

            echo "<div class='lg:mb-0'>";
            if ($numAchievements > 0) {
                $numAuthors = count($authorInfo);

                echo "<span class='font-bold'>" . __res('author', $numAuthors) . ":</span> ";
                $i = 0;
                foreach ($authorInfo as $author => $achievementCount) {
                    echo userAvatar($author, icon: false);
                    echo " (" . $achievementCount . ")";
                    if (++$i !== $numAuthors) {
                        echo ', ';
                    }
                }
            }

            // Display claim information
            if ($user !== null && $flagParam == $officialFlag && !$isEventGame) {
                ?>
                    <x-game.claim-info
                        :claimData="$claimData"
                        :gameId="$gameID"
                        :userPermissions="$permissions"
                    />
                <?php
            }
            echo "</div>";

            echo "<div class='my-8 lg:my-4 lg:flex justify-between w-full gap-x-4'>";
            ?>
                <x-game.achievements-list-meta
                    :isOfficial="$isOfficial"
                    :numAchievements="$numAchievements"
                    :numMissableAchievements="$numMissableAchievements"
                    :totalPossible="$totalPossible"
                    :totalPossibleTrueRatio="$totalPossibleTrueRatio"
                />
            <?php
            echo "</div>";

            // Progression component (desktop only)
            if ($user !== null && $numAchievements > 0) {
                echo "<div class='mt-4 mb-4 lg:hidden'>";
                ?>
                <x-game.current-progress.root
                    :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                    :gameId="$gameID"
                    :isBeatable="$isGameBeatable"
                    :isBeatenHardcore="$isBeatenHardcore"
                    :isBeatenSoftcore="$isBeatenSoftcore"
                    :isCompleted="!is_null($userGameProgressionAwards['completed'])"
                    :isMastered="!is_null($userGameProgressionAwards['mastered'])"
                    :isEvent="$isEventGame"
                    :numEarnedHardcoreAchievements="$numEarnedHardcore"
                    :numEarnedHardcorePoints="$totalEarnedHardcore"
                    :numEarnedSoftcoreAchievements="$numEarnedCasual"
                    :numEarnedSoftcorePoints="$totalEarnedCasual"
                    :numEarnedWeightedPoints="$totalEarnedTrueRatio"
                    :totalAchievementsCount="$numAchievements"
                    :totalPointsCount="$totalPossible"
                />
                <?php
                echo "</div>";
            }

            if ($numAchievements > 1) {
                echo "<div class='flex flex-col sm:flex-row-reverse sm:items-end justify-between w-full py-3'>";

                $hasCompletionOrMastery = ($numEarnedCasual === $numAchievements) || ($numEarnedHardcore === $numAchievements);
                $canShowHideUnlockedAchievements = $user && ($numEarnedCasual > 0 || $numEarnedHardcore > 0) && !$hasCompletionOrMastery;
                ?>
                    <x-game.achievements-list-filters
                        :canShowHideUnlockedAchievements="$canShowHideUnlockedAchievements"
                        :numMissableAchievements="$gameMetaBindings['numMissableAchievements']"
                    />
                <?php
                RenderGameSort($isFullyFeaturedGame, $flagParam, $officialFlag, $gameID, $sortBy, canSortByType: $isGameBeatable);
                echo "</div>";
            }

            if (isset($achievementData)) {
                ?>
                    <x-game.achievements-list.root
                        :achievements="$achievementData"
                        :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                        :isCreditDialogEnabled="$user && $flagParam != $unofficialFlag"
                        :showAuthorNames="!$isOfficial && isset($user) && $permissions >= Permissions::JuniorDeveloper"
                        :totalPlayerCount="$numDistinctPlayers"
                    />
                <?php
            }
        }

        if (!$isFullyFeaturedGame) {
            if (!empty($relatedGames)) {
                $controller = new RelatedGamesTableController(new GameListService());
                $view = $controller(request());
                echo $view->render();

                if (count($gameEvents) > 0) {
                    $icon = getSystemIconUrl(101);
                    echo '<h2 class="flex gap-x-2 items-center text-h3">';
                    echo "<img src=\"$icon\" alt=\"Console icon\" width=\"24\" height=\"24\">";
                    echo '<span>Related Events</span>';
                    echo '</h2>';

                    echo '<div><table class="table-highlight mb-4"><tbody>';
                    foreach ($gameEvents as $game) {
                        echo '<tr><td>';
                        ?>
                            <x-game.multiline-avatar
                                :gameId="$game['gameIDAlt']"
                                :gameTitle="$game['Title']"
                                :gameImageIcon="$game['ImageIcon']"
                            />
                        <?php
                        echo '</td></tr>';
                    }
                    echo '</tbody></table></div>';
                }

                if (count($gameHubs) > 0) {
                    $icon = getSystemIconUrl(100);
                    echo '<h2 class="flex gap-x-2 items-center text-h3">';
                    echo "<img src=\"$icon\" alt=\"Console icon\" width=\"24\" height=\"24\">";
                    echo '<span>Related Hubs</span>';
                    echo '</h2>';

                    echo '<div><table class="table-highlight mb-4"><tbody>';
                    foreach ($gameHubs as $game) {
                        echo '<tr><td>';
                        ?>
                            <x-game.multiline-avatar
                                :gameId="$game['gameIDAlt']"
                                :gameTitle="$game['Title']"
                                :gameImageIcon="$game['ImageIcon']"
                            />
                        <?php
                        echo '</td></tr>';
                    }
                    echo '</tbody></table></div>';
                }
            }
        }

        echo "<div class='my-5'>";
        ?>
            <x-game.link-buttons
                :allowedLinks="['forum-topic']"
                :gameForumTopicId="$forumTopicID"
                :gameId="$gameID"
            />
        <?php
        echo "</div>";

        if ($isFullyFeaturedGame) {
            $recentPlayerData = getGameRecentPlayers($gameID, 10);
            if (!empty($recentPlayerData)) {
                echo "<div class='mt-6 mb-8'>";
                ?>
                    <x-game.recent-game-players
                        :gameId="$gameID"
                        :gameTitle="$gameTitle"
                        :recentPlayerData="$recentPlayerData"
                    />
                <?php
                echo "</div>";
            }

            RenderCommentsComponent($user, $numArticleComments, $commentData, $gameID, ArticleType::Game, $permissions);
        }
        ?>
    </div>
@if ($isFullyFeaturedGame)
    <x-slot name="sidebar">
        <?php
        echo "<div class='component text-center mb-6'>";
        echo "<img class='max-w-full rounded-sm' src='" . media_asset($gameData['ImageBoxArt']) . "' alt='Boxart'>";
        echo "</div>";

        echo "<div class='component'>";
        ?>
            <x-game.link-buttons
                :gameAchievementsCount="$numAchievements"
                :gameForumTopicId="$forumTopicID"
                :gameGuideUrl="$guideURL"
                :gameId="$gameID"
                :isViewingOfficial="$flagParam !== $unofficialFlag"
            />
        <?php
        echo "</div>";

        // Progression component (mobile only)
        if ($user !== null && $numAchievements > 0 && $isOfficial) {
            echo "<div class='mb-5 hidden lg:block'>";
            ?>
            <x-game.current-progress.root
                :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                :gameId="$gameID"
                :isBeatable="$isGameBeatable"
                :isBeatenHardcore="$isBeatenHardcore"
                :isBeatenSoftcore="$isBeatenSoftcore"
                :isCompleted="!is_null($userGameProgressionAwards['completed'])"
                :isMastered="!is_null($userGameProgressionAwards['mastered'])"
                :isEvent="$isEventGame"
                :numEarnedHardcoreAchievements="$numEarnedHardcore"
                :numEarnedHardcorePoints="$totalEarnedHardcore"
                :numEarnedSoftcoreAchievements="$numEarnedCasual"
                :numEarnedSoftcorePoints="$totalEarnedCasual"
                :numEarnedWeightedPoints="$totalEarnedTrueRatio"
                :totalAchievementsCount="$numAchievements"
                :totalPointsCount="$totalPossible"
            />
            <?php
            echo "</div>";
        }

        if (!empty($gameSubsets)) {
            RenderGameAlts($gameSubsets, 'Subsets');
        }

        if (!empty($gameAlts)) {
            RenderGameAlts($gameAlts, 'Similar Games');
        }

        if (!empty($gameHubs)) {
            RenderGameAlts($gameHubs, 'Hubs');
        }

        if ($user !== null && $numAchievements > 0) {
            $gameModel = Game::find($gameID);
            $followedUserCompletion = CompareUnlocksController::getFollowedUsersCompletion($userModel, $gameModel);
            ?>
            <x-game.compare-progress
                :game="$gameModel"
                :user="$userModel"
                :followedUserCompletion="$followedUserCompletion"
            />
            <?php
        }

        if ($numAchievements > 0 && $isOfficial) {
            echo "<div id='achdistribution' class='component' >";
            echo "<h2 class='text-h3'>Achievement Distribution</h2>";
            echo "<div id='chart_distribution' class='min-h-[260px]'></div>";
            echo "</div>";

            RenderTopAchieversComponent($user, $gameTopAchievers['HighScores'], $gameTopAchievers['Masters']);
        }

        if (isValidConsoleId($consoleID)) {
            RenderGameLeaderboardsComponent($lbData, $forumTopicID);
        }
        ?>
    </x-slot>
@endif
</x-app-layout>
