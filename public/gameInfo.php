<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\RatingType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\TicketFilters;
use App\Community\Enums\UserGameListType;
use App\Community\Models\UserGameListEntry;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\ImageType;
use App\Platform\Enums\UnlockMode;
use App\Site\Enums\Permissions;
use App\Site\Enums\UserPreference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;

$gameID = (int) request('game');
if (empty($gameID)) {
    abort(404);
}

$friendScores = [];
if (authenticateFromCookie($user, $permissions, $userDetails)) {
    getAllFriendsProgress($user, $gameID, $friendScores);
}
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

$defaultSort = 1;
if (isset($user)) {
    $defaultSort = 13;
}
$sortBy = requestInputSanitized('s', $defaultSort, 'integer');

if (!isset($user) && ($sortBy == 3 || $sortBy == 13)) {
    $sortBy = 1;
}

$numAchievements = getGameMetadata($gameID, $user, $achievementData, $gameData, $sortBy, null, $flagParam, metrics:true);

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
$gameSubsets = [];
$subsetPrefix = $gameData['Title'] . " [Subset - ";
foreach ($relatedGames as $gameAlt) {
    if ($gameAlt['ConsoleName'] == 'Hubs') {
        $gameHubs[] = $gameAlt;
    } else {
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
$claimListLength = 0;
$isGameBeatable = false;
$isBeatenHardcore = false;
$isBeatenSoftcore = false;
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

    $numLeaderboards = getLeaderboardsForGame($gameID, $lbData, $user);

    if (isset($user)) {
        // Determine if the logged in user is the sole author of the set
        $isSoleAuthor = checkIfSoleDeveloper($user, $gameID);

        // Determine if the logged in user has any progression awards for this set
        $userGameProgressionAwards = getUserGameProgressionAwards($gameID, $user);
        $isBeatenHardcore = !is_null($userGameProgressionAwards['beaten-hardcore']);
        $isBeatenSoftcore = !is_null($userGameProgressionAwards['beaten-softcore']);
    }

    $screenshotWidth = 200;
    $screenshotMaxHeight = 240; // corresponds to the DS screen aspect ratio

    // Quickly calculate earned/potential
    $totalEarnedCasual = 0;
    $totalEarnedHardcore = 0;
    $numEarnedCasual = 0;
    $numEarnedHardcore = 0;
    $totalPossible = 0;

    // Quickly calculate if the player potentially has an unawarded beaten game award
    $totalProgressionAchievements = 0;
    $totalWinConditionAchievements = 0;
    $totalEarnedProgression = 0;
    $totalEarnedWinCondition = 0;

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
                    if (isset($nextAch['DateEarned']) || isset($nextAch['DateEarnedHardcore'])) {
                        $totalEarnedProgression++;
                    }
                } elseif ($nextAch['type'] == AchievementType::WinCondition) {
                    $totalWinConditionAchievements++;
                    if (isset($nextAch['DateEarned']) || isset($nextAch['DateEarnedHardcore'])) {
                        $totalEarnedWinCondition++;
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

    // If the game is beatable, the user has met the requirements to receive the
    // beaten game award, and they do not currently have that award, give it to them.
    if ($isGameBeatable) {
        $neededProgressions = $totalProgressionAchievements > 0 ? $totalProgressionAchievements : 0;
        $neededWinConditions = $totalWinConditionAchievements > 0 ? 1 : 0;
        if (
            $totalEarnedProgression === $neededProgressions
            && $totalEarnedWinCondition >= $neededWinConditions
            && !$isBeatenHardcore
            && !$isBeatenSoftcore
        ) {
            $beatenGameRetVal = testBeatenGame($gameID, $user, true);
            $isBeatenHardcore = $beatenGameRetVal['isBeatenHardcore'];
            $isBeatenSoftcore = $beatenGameRetVal['isBeatenSoftcore'];
        }
    }

    // Get the top ten players at this game:
    $gameTopAchievers = getGameTopAchievers($gameID);

    $claimData = getClaimData($gameID, true);
    $claimListLength = count($claimData);
}

$gameRating = getGameRating($gameID, $user);
$minimumNumberOfRatingsToDisplay = 5;

sanitize_outputs(
    $gameTitle,
    $consoleName,
    $richPresenceData,
    $user,
);
?>

<?php if ($isFullyFeaturedGame): ?>
    <?php
        function generateGameMetaDescription(
            string $gameTitle,
            string $consoleName,
            int $numAchievements = 0,
            int $gamePoints = 0,
            bool $isEventGame = false,
        ): string {
            if ($isEventGame) {
                return "$gameTitle: An event at RetroAchievements. Check out the page for more details on this unique challenge.";
            } elseif ($numAchievements === 0 || $gamePoints === 0) {
                return "No achievements have been created yet for $gameTitle. Join RetroAchievements to request achievements for $gameTitle and earn achievements on many other classic games.";
            }

            $localizedPoints = localized_number($gamePoints);

            return "There are $numAchievements achievements worth $localizedPoints points. $gameTitle for $consoleName - explore and compete on this classic game at RetroAchievements.";
        }

        RenderOpenGraphMetadata(
            $pageTitle,
            "game",
            media_asset($gameData['ImageIcon']),
            generateGameMetaDescription(
                $gameTitle,
                $consoleName,
                $numAchievements,
                $totalPossible,
                $isEventGame
            )
        );
    ?>
<?php endif ?>

<?php if ($gate): ?>
    <?php RenderContentStart($pageTitle) ?>
    <article>
    <?php
        echo Blade::render('
            <x-game.mature-content-gate
                :gameId="$gameId"
                :gameTitle="$gameTitle"
                :consoleId="$consoleId"
                :consoleName="$consoleName"
                :userWebsitePrefs="$userWebsitePrefs"
            />
        ', [
            'gameId' => $gameID,
            'gameTitle' => $gameTitle,
            'consoleId' => $consoleID,
            'consoleName' => $consoleName,
            'userWebsitePrefs' => $userDetails['websitePrefs'] ?? 0,
        ]);
    ?>
    </article>
    <?php return ?>
<?php endif ?>

<?php RenderContentStart($pageTitle); ?>
<?php if ($isFullyFeaturedGame): ?>
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
            function generateEmptyBucketsWithBounds(int $numAchievements): array
            {
                $DYNAMIC_BUCKETING_THRESHOLD = 44;
                $GENERATED_RANGED_BUCKETS_COUNT = 20;

                // Enable bucketing based on the number of achievements in the set.
                // This number was picked arbitrarily, but generally reflects when we start seeing
                // width constraints in the Achievements Distribution bar chart.
                $isDynamicBucketingEnabled = $numAchievements >= $DYNAMIC_BUCKETING_THRESHOLD;

                // If bucketing is enabled, we'll dynamically generate 19 buckets. The final 20th
                // bucket will contain all users who have completed/mastered the game.
                $bucketCount = $isDynamicBucketingEnabled ? $GENERATED_RANGED_BUCKETS_COUNT : $numAchievements;

                // Bucket size is determined based on the total number of achievements in the set.
                // If bucketing is enabled, we aim for roughly 20 buckets (hence dividing by $bucketCount).
                // If bucketing is not enabled, each achievement gets its own bucket (bucket size is 1).
                $bucketSize = $isDynamicBucketingEnabled ? ($numAchievements - 1) / $bucketCount : 1;

                $buckets = [];
                $currentUpperBound = 1;
                for ($i = 0; $i < $bucketCount; $i++) {
                    if ($isDynamicBucketingEnabled) {
                        $start = $i === 0 ? 1 : $currentUpperBound + 1;
                        $end = intval(round($bucketSize * ($i + 1)));
                        $buckets[$i] = ['start' => $start, 'end' => $end, 'hardcore' => 0, 'softcore' => 0];

                        $currentUpperBound = $end;
                    } else {
                        $buckets[$i] = ['start' => $i + 1, 'end' => $i + 1, 'hardcore' => 0, 'softcore' => 0];
                    }
                }

                return [$buckets, $isDynamicBucketingEnabled];
            }

            function findBucketIndex(array $buckets, int $achievementNumber): int
            {
                $low = 0;
                $high = count($buckets) - 1;

                // Perform a binary search.
                while ($low <= $high) {
                    $mid = intdiv($low + $high, 2);
                    if ($achievementNumber >= $buckets[$mid]['start'] && $achievementNumber <= $buckets[$mid]['end']) {
                        return $mid;
                    }
                    if ($achievementNumber < $buckets[$mid]['start']) {
                        $high = $mid - 1;
                    } else {
                        $low = $mid + 1;
                    }
                }

                // Error: This should not happen unless something is terribly wrong with the page.
                return -1;
            }

            function calculateBuckets(
                array &$buckets,
                bool $isDynamicBucketingEnabled,
                int $numAchievements,
                array $achDist,
                array $achDistHardcore
            ): array {
                $largestWonByCount = 0;

                // Iterate through the achievements and distribute them into the buckets.
                for ($i = 1; $i < $numAchievements; $i++) {
                    // Determine the bucket index based on the current achievement number.
                    $targetBucketIndex = $isDynamicBucketingEnabled ? findBucketIndex($buckets, $i) : $i - 1;

                    // Distribute the achievements into the bucket by adding the number of hardcore
                    // users who achieved it and the number of softcore users who achieved it to
                    // the respective counts.
                    $wonByUserCount = $achDist[$i];
                    $buckets[$targetBucketIndex]['hardcore'] += $achDistHardcore[$i];
                    $buckets[$targetBucketIndex]['softcore'] += $wonByUserCount - $achDistHardcore[$i];

                    // We need to also keep tracked of `largestWonByCount`, which is later used for chart
                    // configuration, such as determining the number of gridlines to show.
                    $currentTotal = $buckets[$targetBucketIndex]['hardcore'] + $buckets[$targetBucketIndex]['softcore'];
                    $largestWonByCount = max($currentTotal, $largestWonByCount);
                }

                return [$buckets, $largestWonByCount];
            }

            function handleAllAchievementsCase(int $numAchievements, array $achDist, array $achDistHardcore, array &$buckets): int
            {
                if ($numAchievements <= 0) {
                    return 0;
                }

                // Add a bucket for the users who have earned all achievements.
                $buckets[] = [
                    'hardcore' => $achDistHardcore[$numAchievements],
                    'softcore' => $achDist[$numAchievements] - $achDistHardcore[$numAchievements],
                ];

                // Calculate the total count of users who have earned all achievements.
                // This will later be used for chart configuration in determining the
                // number of gridlines to show on one of the axes.
                $allAchievementsCount = (
                    $achDistHardcore[$numAchievements] + ($achDist[$numAchievements] - $achDistHardcore[$numAchievements])
                );

                return $allAchievementsCount;
            }

            function printBucketIteration(int $bucketIteration, int $numAchievements, array $bucket, string $label): void
            {
                echo "[ {v:$bucketIteration, f:\"$label\"}, {$bucket['hardcore']}, {$bucket['softcore']} ]";
            }

            function generateBucketLabelsAndValues(int $numAchievements, array $buckets): array
            {
                $bucketLabels = [];
                $hAxisValues = [];
                $bucketIteration = 0;
                $bucketCount = count($buckets);

                // Loop through each bucket to generate their labels and values.
                foreach ($buckets as $index => $bucket) {
                    if ($bucketIteration++ > 0) {
                        echo ", ";
                    }

                    // Is this the last bucket? If so, we only want it to include
                    // players who have earned all the achievements, not a range.
                    if ($index == $bucketCount - 1) {
                        $label = "Earned $numAchievements achievements";
                        printBucketIteration($bucketIteration, $numAchievements, $bucket, $label);

                        $hAxisValues[] = $numAchievements;
                    } else {
                        // For other buckets, the label indicates the range of achievements that
                        // the bucket represents.
                        $start = $bucket['start'];
                        $end = $bucket['end'];

                        // Pluralize 'achievement' if the range contains more than one achievement.
                        $plural = $end > 1 ? 's' : '';
                        $label = "Earned $start achievement$plural";
                        if ($start !== $end) {
                            $label = "Earned $start-$end achievement$plural";
                        }

                        printBucketIteration($bucketIteration, $numAchievements, $bucket, $label);

                        $hAxisValues[] = $start;
                    }
                }

                return $hAxisValues;
            }

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
            colors: ['#cc9900', '#186DEE'],
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
    <script>
    var lastKnownAchRating = <?= $gameRating[RatingType::Achievement]['AverageRating'] ?>;
    var lastKnownGameRating = <?= $gameRating[RatingType::Game]['AverageRating'] ?>;
    var lastKnownAchRatingCount = <?= $gameRating[RatingType::Achievement]['RatingCount'] ?>;
    var lastKnownGameRatingCount = <?= $gameRating[RatingType::Game]['RatingCount'] ?>;

    function SetLitStars(container, numStars) {
        $(container + ' a').removeClass('starlit');
        $(container + ' a').removeClass('starhalf');

        if (numStars >= 0.5) {
            $(container + ' a:first-child').addClass('starhalf');
        }
        if (numStars >= 1.5) {
            $(container + ' a:first-child + a').addClass('starhalf');
        }
        if (numStars >= 2.5) {
            $(container + ' a:first-child + a + a').addClass('starhalf');
        }
        if (numStars >= 3.5) {
            $(container + ' a:first-child + a + a + a').addClass('starhalf');
        }
        if (numStars >= 4.5) {
            $(container + ' a:first-child + a + a + a + a').addClass('starhalf');
        }

        if (numStars >= 1) {
            $(container + ' a:first-child').removeClass('starhalf');
            $(container + ' a:first-child').addClass('starlit');
        }

        if (numStars >= 2) {
            $(container + ' a:first-child + a').removeClass('starhalf');
            $(container + ' a:first-child + a').addClass('starlit');
        }

        if (numStars >= 3) {
            $(container + ' a:first-child + a + a').removeClass('starhalf');
            $(container + ' a:first-child + a + a').addClass('starlit');
        }

        if (numStars >= 4) {
            $(container + ' a:first-child + a + a + a').removeClass('starhalf');
            $(container + ' a:first-child + a + a + a').addClass('starlit');
        }

        if (numStars >= 5) {
            $(container + ' a:first-child + a + a + a + a').removeClass('starhalf');
            $(container + ' a:first-child + a + a + a + a').addClass('starlit');
        }
    }

    function UpdateRating(container, label, rating, raters) {
        const pluralizeVotes = (count) => count === 1 ? 'vote' : 'votes';
        if (raters < <?= $minimumNumberOfRatingsToDisplay ?>) {
            SetLitStars(container, 0);
            label.html(`More ratings needed (${raters} ${pluralizeVotes(raters)})`);
        } else {
            SetLitStars(container, rating);
            label.html(`Rating: ${rating.toFixed(2)} (${raters} ${pluralizeVotes(raters)})`);
        }
    }

    function UpdateRatings() {
        UpdateRating('#ratinggame', $('.ratinggamelabel'), lastKnownGameRating, lastKnownGameRatingCount);
        UpdateRating('#ratingach', $('.ratingachlabel'), lastKnownAchRating, lastKnownAchRatingCount);
    }

    function SubmitRating(gameID, ratingObjectType, value) {
        $.post('/request/game/update-rating.php', {
            game: gameID,
            type: ratingObjectType,
            rating: value
        })
            .done(function () {
                $.post('/request/game/rating.php', {
                    game: gameID,
                })
                    .done(function (results) {
                        lastKnownGameRating = parseFloat(results.Ratings['Game']);
                        lastKnownAchRating = parseFloat(results.Ratings['Achievements']);
                        lastKnownGameRatingCount = results.Ratings['GameNumVotes'];
                        lastKnownAchRatingCount = results.Ratings['AchievementsNumVotes'];

                        UpdateRatings();

                        const pluralizeStarCount = (count) => count === 1 ? 'star' : 'stars';
                        if (ratingObjectType == <?= RatingType::Game ?>) {
                            index = ratinggametooltip.indexOf('Your rating: ') + 13;
                            index2 = ratinggametooltip.indexOf('</td>', index);
                            ratinggametooltip = ratinggametooltip.substring(0, index) + value + ` ${pluralizeStarCount(value)}` + '<br><i>Distribution may have changed</i>' + ratinggametooltip.substring(index2);
                        } else {
                            index = ratingachtooltip.indexOf('Your rating: ') + 13;
                            index2 = ratingachtooltip.indexOf('</td>', index);
                            ratingachtooltip = ratingachtooltip.substring(0, index) + value + ` ${pluralizeStarCount(value)}` + '<br><i>Distribution may have changed</i>' + ratingachtooltip.substring(index2);
                        }
                    });
            });
    }

    $(function () {
        $('.starimg').hover(
            function () {
                // On hover

                if ($(this).parent().is($('#ratingach'))) {
                    // Ach:
                    var numStars = 0;
                    if ($(this).hasClass('1star')) {
                        numStars = 1;
                    } else if ($(this).hasClass('2star')) {
                        numStars = 2;
                    } else if ($(this).hasClass('3star')) {
                        numStars = 3;
                    } else if ($(this).hasClass('4star')) {
                        numStars = 4;
                    } else if ($(this).hasClass('5star')) {
                        numStars = 5;
                    }

                    SetLitStars('#ratingach', numStars);
                } else {
                    // Game:
                    var numStars = 0;
                    if ($(this).hasClass('1star')) {
                        numStars = 1;
                    } else if ($(this).hasClass('2star')) {
                        numStars = 2;
                    } else if ($(this).hasClass('3star')) {
                        numStars = 3;
                    } else if ($(this).hasClass('4star')) {
                        numStars = 4;
                    } else if ($(this).hasClass('5star')) {
                        numStars = 5;
                    }

                    SetLitStars('#ratinggame', numStars);
                }
            });

        $('.rating').hover(
            function () {
                // On hover
            },
            function () {
                // On leave
                UpdateRatings();
            });

        $('.starimg').click(function () {

            var numStars = 0;
            if ($(this).hasClass('1star')) {
                numStars = 1;
            } else if ($(this).hasClass('2star')) {
                numStars = 2;
            } else if ($(this).hasClass('3star')) {
                numStars = 3;
            } else if ($(this).hasClass('4star')) {
                numStars = 4;
            } else if ($(this).hasClass('5star')) {
                numStars = 5;
            }

            var ratingType = 1;
            if ($(this).parent().is($('#ratingach'))) {
                ratingType = 3;
            }

            // Do an optimistic update to make performance seem better.
            const yourRatingText = document.getElementById('your-game-rating');
            yourRatingText.innerHTML = `Your rating: ${numStars} ${numStars === 1 ? 'star' : 'stars'}`;

            SubmitRating(<?= $gameID ?>, ratingType, numStars);
        });

    });

    function getSetRequestInformation(user, gameID) {
        $.post('/request/user-game-list/set-requests.php', {
            game: gameID,
            user: user,
        })
            .done(function (results) {
                var remaining = parseInt(results.remaining);
                var gameTotal = parseInt(results.gameRequests);
                var thisGame = results.requestedThisGame;

                var $requestButton = $('.setRequestLabel');
                $requestButton.show();
                $('.gameRequestsLabel').html('Set Requests: <a href=\'/setRequestors.php?g=' + gameID + '\'>' + gameTotal + '</a>');
                $('.userRequestsLabel').html('User Requests Remaining: <a href=\'/setRequestList.php?u=' + user + '\'>' + remaining + '</a>');

                // If the user has not requested a set for this game
                if (thisGame == 0) {
                    if (remaining <= 0) {
                        $requestButton.text('No Requests Remaining');

                        //Remove clickable text
                        $requestButton.each(function () {
                            $($(this).text()).replaceAll(this);
                        });
                    } else {
                        $requestButton.text('Request Set');
                    }
                } else {
                    $requestButton.text('Withdraw Request');
                }
            });
    }

    function submitSetRequest(user, gameID) {
        $.post('/request/user-game-list/toggle.php', {
            game: gameID,
            type: '<?= UserGameListType::AchievementSetRequest ?>'
        })
            .done(function () {
                getSetRequestInformation('<?= $user ?>', <?= $gameID ?>);
            });
    }

    $(function () {
        // When the set request text is clicked
        $('.setRequestLabel').click(function () {
            submitSetRequest('<?= $user ?>', <?= $gameID ?>);
        });

        if ($('.setRequestLabel').length) {
            getSetRequestInformation('<?= $user ?>', <?= $gameID ?>);
        }
    });
    </script>
<?php endif ?>
<article>
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
        $renderedTitle = renderGameTitle($gameTitle);
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
            'permissions' => $permissions,
            'publisher' => $publisher,
            'released' => $released,
            'user' => $user,
        ];

        $initializedProgressComponent = Blade::render('
            <x-game.current-progress.root
                :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                :gameId="$gameId"
                :isBeatable="$isBeatable"
                :isBeatenHardcore="$isBeatenHardcore"
                :isBeatenSoftcore="$isBeatenSoftcore"
                :isCompleted="$isCompleted"
                :isMastered="$isMastered"
                :isEvent="$isEvent"
                :numEarnedHardcoreAchievements="$numEarnedHardcoreAchievements"
                :numEarnedHardcorePoints="$numEarnedHardcorePoints"
                :numEarnedSoftcoreAchievements="$numEarnedSoftcoreAchievements"
                :numEarnedSoftcorePoints="$numEarnedSoftcorePoints"
                :numEarnedWeightedPoints="$numEarnedWeightedPoints"
                :totalAchievementsCount="$totalAchievementsCount"
                :totalPointsCount="$totalPointsCount"
            />
        ', [
            'beatenGameCreditDialogContext' => $beatenGameCreditDialogContext,
            'gameId' => $gameID,
            'isBeatable' => $isGameBeatable && config('feature.beat') === true,
            'isBeatenHardcore' => $isBeatenHardcore,
            'isBeatenSoftcore' => $isBeatenSoftcore,
            'isCompleted' => !is_null($userGameProgressionAwards['completed']),
            'isMastered' => !is_null($userGameProgressionAwards['mastered']),
            'isEvent' => $isEventGame,
            'numEarnedHardcoreAchievements' => $numEarnedHardcore,
            'numEarnedHardcorePoints' => $totalEarnedHardcore,
            'numEarnedSoftcoreAchievements' => $numEarnedCasual,
            'numEarnedSoftcorePoints' => $totalEarnedCasual,
            'numEarnedWeightedPoints' => $totalEarnedTrueRatio,
            'totalAchievementsCount' => $numAchievements,
            'totalPointsCount' => $totalPossible,
        ]);

        echo Blade::render('
            <x-game.heading
                :gameId="$gameID"
                :gameTitle="$gameTitle"
                :consoleId="$consoleID"
                :consoleName="$consoleName"
                :user="$user"
                :userPermissions="$permissions"
            />
        ', $gameMetaBindings);

        echo Blade::render('
            <x-game.primary-meta
                :developer="$developer"
                :publisher="$publisher"
                :genre="$genre"
                :released="$released"
                :imageIcon="$imageIcon"
                :metaKind="$isFullyFeaturedGame ? \'Game\' : \'Hub\'"
            >
                @if ($isFullyFeaturedGame)
                    <x-game.primary-meta-row-item label="Developer" :metadataValue="$developer" :gameHubs="$gameHubs" :altLabels="[\'Hacker\']" />
                    <x-game.primary-meta-row-item label="Publisher" :metadataValue="$publisher" :gameHubs="$gameHubs" :altLabels="[\'Hacks\']" />
                    <x-game.primary-meta-row-item label="Genre" :metadataValue="$genre" :gameHubs="$gameHubs" :altLabels="[\'Subgenre\']" />
                @else
                    <x-game.primary-meta-row-item label="Developer" :metadataValue="$developer" />
                    <x-game.primary-meta-row-item label="Publisher" :metadataValue="$publisher" />
                    <x-game.primary-meta-row-item label="Genre" :metadataValue="$genre" />
                @endif

                <x-game.primary-meta-row-item label="Released" :metadataValue="$released" />
            </x-game.primary-meta>
        ', $gameMetaBindings);

        if ($isFullyFeaturedGame) {
            echo Blade::render('
                <x-game.screenshots :titleImageSrc="$titleImageSrc" :ingameImageSrc="$ingameImageSrc" />
            ', ['titleImageSrc' => $imageTitle, 'ingameImageSrc' => $imageIngame]);
        }

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
                    echo "<div><a class='btn btn-link' href='/managehashes.php?g=$gameID'>Manage Hashes</a></div>";
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

                if ($permissions >= Permissions::Developer) {
                    echo "<form action='/request/game/recalculate-points-ratio.php' method='post'>";
                    echo csrf_field();
                    echo "<input type='hidden' name='game' value='$gameID'>";
                    echo "<button class='btn'>Recalculate True Ratios</button>";
                    echo "</form>";
                }

                // Display the claims links if not an event game
                if (!$isEventGame) {
                    if ($permissions >= Permissions::Developer) {
                        $gameMetaBindings['developListType'] = UserGameListType::Develop;
                        echo Blade::render('
                            <x-game.add-to-list
                                :gameId="$gameID"
                                :type="$developListType"
                                :user="$user"
                            />
                        ', $gameMetaBindings);
                    }

                    echo Blade::render('
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
                    ', $gameMetaBindings);

                }

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
                echo Blade::render("<x-fas-info-circle class='w-5 h-5' aria-hidden='true' />");
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
            $renderRatingControl = function ($label, $containername, $labelname, $ratingData) use ($minimumNumberOfRatingsToDisplay) {
                echo "<div>";

                echo "<h2 class='text-h4 mb-0'>$label</h2>";

                $yourRating = ($ratingData['UserRating'] > 0)
                    ? $ratingData['UserRating'] . " " . strtolower(__res('vote.star', $ratingData['UserRating'])) // "2 stars"
                    : 'not rated';

                $voters = $ratingData['RatingCount'];
                if ($voters < $minimumNumberOfRatingsToDisplay) {
                    $labelcontent = "More ratings needed ($voters " . strtolower(__res('vote', $voters)) . ")";

                    $star1 = $star2 = $star3 = $star4 = $star5 = "";
                    $tooltip = "<div class='tooltip-body flex items-start' style='max-width: 400px'>";
                    $tooltip .= "<table><tr><td class='whitespace-nowrap'>Your rating: $yourRating</td></tr></table>";
                    $tooltip .= "</div>";
                } else {
                    $rating = $ratingData['AverageRating'];
                    $labelcontent = "Rating: " . number_format($rating, 2) . " ($voters " . strtolower(__res('vote', $voters)) . ")"; // "Rating: 4.78 (20 votes)"

                    $percent1 = round($ratingData['Rating1'] * 100 / $voters);
                    $percent2 = round($ratingData['Rating2'] * 100 / $voters);
                    $percent3 = round($ratingData['Rating3'] * 100 / $voters);
                    $percent4 = round($ratingData['Rating4'] * 100 / $voters);
                    $percent5 = round($ratingData['Rating5'] * 100 / $voters);

                    $tooltip = "<div class='tooltip-body flex items-start' style='max-width: 400px'>";
                    $tooltip .= "<table>";
                    $tooltip .= "<tr><td colspan=3>Your rating: $yourRating</td></tr>";
                    $tooltip .= "<tr><td class='whitespace-nowrap'>5 star</td><td>";
                    $tooltip .= "<div class='progressbar w-24'><div class='completion' style='width:$percent5%' /></div>";
                    $tooltip .= "</td><td>$percent5%</td/></tr>";
                    $tooltip .= "<tr><td class='whitespace-nowrap'>4 star</td><td>";
                    $tooltip .= "<div class='progressbar w-24'><div class='completion' style='width:$percent4%' /></div>";
                    $tooltip .= "</td><td>$percent4%</td/></tr>";
                    $tooltip .= "<tr><td class='whitespace-nowrap'>3 star</td><td>";
                    $tooltip .= "<div class='progressbar w-24'><div class='completion' style='width:$percent3%' /></div>";
                    $tooltip .= "</td><td>$percent3%</td/></tr>";
                    $tooltip .= "<tr><td class='whitespace-nowrap'>2 star</td><td>";
                    $tooltip .= "<div class='progressbar w-24'><div class='completion' style='width:$percent2%' /></div>";
                    $tooltip .= "</td><td>$percent2%</td/></tr>";
                    $tooltip .= "<tr><td class='whitespace-nowrap'>1 star</td><td>";
                    $tooltip .= "<div class='progressbar w-24'><div class='completion' style='width:$percent1%' /></div>";
                    $tooltip .= "</td><td>$percent1%</td/></tr>";
                    $tooltip .= "</table>";
                    $tooltip .= "</div>";

                    $star1 = ($rating >= 1.0) ? "starlit" : (($rating >= 0.5) ? "starhalf" : "");
                    $star2 = ($rating >= 2.0) ? "starlit" : (($rating >= 1.5) ? "starhalf" : "");
                    $star3 = ($rating >= 3.0) ? "starlit" : (($rating >= 2.5) ? "starhalf" : "");
                    $star4 = ($rating >= 4.0) ? "starlit" : (($rating >= 3.5) ? "starhalf" : "");
                    $star5 = ($rating >= 5.0) ? "starlit" : (($rating >= 4.5) ? "starhalf" : "");
                }

                echo "<div class='rating' id='$containername'>";
                echo "<a class='starimg $star1 1star'>1</a>";
                echo "<a class='starimg $star2 2star'>2</a>";
                echo "<a class='starimg $star3 3star'>3</a>";
                echo "<a class='starimg $star4 4star'>4</a>";
                echo "<a class='starimg $star5 5star'>5</a>";
                echo "</div>";

                echo "<script>var {$containername}tooltip = \"$tooltip\";</script>";

                echo <<<HTML
                    <div
                        class="mt-1"
                        style="float: left; clear: left;"
                        x-data="tooltipComponent(\$el, { staticHtmlContent: {$containername}tooltip })"
                        @mouseover="showTooltip(\$event)"
                        @mouseleave="hideTooltip"
                        @mousemove="trackMouseMovement(\$event)"
                    >
                HTML;

                echo "<p class='$labelname text-2xs'>$labelcontent</p>";
                echo "<p id='your-game-rating' class='text-2xs'>";
                if ($ratingData['UserRating'] > 0) {
                    echo "Your rating: $yourRating";
                }
                echo "</p>";
                echo "</div>";

                echo "</div>";
            };

            echo "<div class='md:float-right mb-4 md:mb-0'>";

            // Only show set request option for logged in users, games without achievements, and core achievement page
            if ($user !== null && $numAchievements == 0 && $flagParam == $officialFlag) {
                echo "<div>";
                echo "<h2 class='text-h4'>Set Requests</h2>";
                echo "<div class='gameRequestsLabel'></div>";
                echo "<div><button type='button' class='btn setRequestLabel hidden'>Request Set</button></div>";
                echo "<div class='userRequestsLabel'></div>";
                echo "</div>";
            }

            if ($user !== null && $numAchievements > 0) {
                $renderRatingControl('Game Rating', 'ratinggame', 'ratinggamelabel', $gameRating[RatingType::Game]);
                echo "<br class='clear-both'>";
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
                echo "<div>";
                $claimExpiration = null;
                $primaryClaim = 1;
                if ($claimListLength > 0) {
                    echo "Claimed by: ";
                    $reviewText = '';
                    foreach ($claimData as $claim) {
                        $revisionText = $claim['SetType'] == ClaimSetType::Revision && $primaryClaim ? " (" . ClaimSetType::toString(ClaimSetType::Revision) . ")" : "";
                        if ($claim['Status'] == ClaimStatus::InReview) {
                            $reviewText = " (" . ClaimStatus::toString(ClaimStatus::InReview) . ")";
                        }
                        $claimExpiration = Carbon::parse($claim['Expiration']);
                        echo userAvatar($claim['User'], icon: false) . $revisionText;
                        if ($claimListLength > 1) {
                            echo ", ";
                        }
                        $claimListLength--;
                        $primaryClaim = 0;
                    }
                    echo $reviewText;

                    if ($claimExpiration) {
                        $isAlreadyExpired = Carbon::parse($claimExpiration)->isPast() ? "Expired" : "Expires";

                        $claimFormattedDate = $claimExpiration->format('d M Y, H:i');
                        $claimTimeAgoDate = $permissions >= Permissions::JuniorDeveloper
                            ? "(" . $claimExpiration->diffForHumans() . ")"
                            : "";

                        // "Expires on: 12 Jun 2023, 01:28 (1 month from now)"
                        echo "<p>$isAlreadyExpired on: $claimFormattedDate $claimTimeAgoDate</p>";
                    }
                } else {
                    if ($numAchievements < 1) {
                        echo "No Active Claims";
                    }
                }
                echo "</div>";
            }
            echo "</div>";

            echo "<div class='my-8 lg:my-4 lg:flex justify-between w-full gap-x-4'>";

            echo "<div>";
            if ($flagParam == $unofficialFlag) {
                echo "There are <b>$numAchievements Unofficial</b> achievements worth <b>" . number_format($totalPossible) . "</b> <span class='TrueRatio'>(" . number_format($totalPossibleTrueRatio) . ")</span> points.<br>";
            } else {
                echo "There are <b>$numAchievements</b> achievements worth <b>" . number_format($totalPossible) . "</b>";
                $localizedTotalPossibleWeightedPoints = localized_number($totalPossibleTrueRatio);
                echo Blade::render("<x-points-weighted-container>($localizedTotalPossibleWeightedPoints)</x-points-weighted-container>");
                echo "points.<br>";
            }
            echo "</div>";

            echo "</div>";

            // Progression component (desktop only)
            if ($user !== null && $numAchievements > 0) {
                echo "<div class='mt-4 mb-4 lg:hidden'>";
                echo $initializedProgressComponent;
                echo "</div>";
            }

            /*
            if( $user !== NULL && $numAchievements > 0 ) {
                $renderRatingControl('Achievements Rating', 'ratingach', 'ratingachlabel', $gameRating[RatingType::Achievement]);
            }
            */

            if ($numAchievements > 1) {
                echo "<div class='flex flex-col sm:flex-row-reverse justify-between w-full py-3'>";

                $hasCompletionOrMastery = ($numEarnedCasual === $numAchievements) || ($numEarnedHardcore === $numAchievements);
                echo "<div>";
                if ($user && ($numEarnedCasual > 0 || $numEarnedHardcore > 0) && !$hasCompletionOrMastery) {
                    echo Blade::render("<x-game.hide-earned-checkbox />");
                }
                echo "</div>";

                RenderGameSort($isFullyFeaturedGame, $flagParam, $officialFlag, $gameID, $sortBy, canSortByType: $isGameBeatable);
                echo "</div>";
            }

            if (isset($achievementData)) {
                echo Blade::render('
                    <x-game.achievements-list.root
                        :achievements="$achievements"
                        :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                        :isCreditDialogEnabled="$isCreditDialogEnabled"
                        :progressionTypeValue="$progressionTypeValue"
                        :showAuthorNames="$showAuthorNames"
                        :totalPlayerCount="$totalPlayerCount"
                        :winConditionTypeValue="$winConditionTypeValue"
                    />
                ', [
                    'achievements' => $achievementData,
                    'beatenGameCreditDialogContext' => $beatenGameCreditDialogContext,
                    'isCreditDialogEnabled' => $user && $flagParam != $unofficialFlag,
                    'progressionTypeValue' => AchievementType::Progression,
                    'showAuthorNames' => !$isOfficial && isset($user) && $permissions >= Permissions::JuniorDeveloper,
                    'totalPlayerCount' => $numDistinctPlayers,
                    'winConditionTypeValue' => AchievementType::WinCondition,
                ]);
            }
        }

        if (!$isFullyFeaturedGame) {
            if (!empty($relatedGames)) {
                RenderGameSort($isFullyFeaturedGame, $flagParam, $officialFlag, $gameID, $sortBy);
                RenderGameAlts($relatedGames);
            }
        }

        echo "<div class='my-5'>";
        RenderLinkToGameForum($gameTitle, $gameID, $forumTopicID, $permissions);
        echo "</div>";

        if ($isFullyFeaturedGame) {
            $recentPlayerData = getGameRecentPlayers($gameID, 10);
            if (!empty($recentPlayerData)) {
                RenderRecentGamePlayers($recentPlayerData, $gameTitle);
            }

            RenderCommentsComponent($user, $numArticleComments, $commentData, $gameID, ArticleType::Game, $permissions);
        }
        ?>
    </div>
</article>
<?php if ($isFullyFeaturedGame): ?>
    <?php view()->share('sidebar', true) ?>
    <aside>
        <?php
        echo "<div class='component text-center mb-6'>";
        echo "<img class='max-w-full rounded-sm' src='" . media_asset($gameData['ImageBoxArt']) . "' alt='Boxart'>";
        echo "</div>";

        echo "<div class='component'>";
        echo "<ul>";
        echo "<li>";
        RenderLinkToGameForum($gameTitle, $gameID, $forumTopicID, $permissions);
        if (!empty($guideURL)) {
            echo "<a class='btn py-2 mb-2 block' href='" . attributeEscape($guideURL) . "'><span class='icon icon-md ml-1 mr-3'>📖</span>Guide</a>";
        }
        echo "</li>";
        if (isset($user)) {
            if ($permissions >= Permissions::Registered) {
                echo "<li><a class='btn py-2 mb-2 block' href='/linkedhashes.php?g=$gameID'><span class='icon icon-md ml-1 mr-3'>💾</span>Supported Game Files</a></li>";
                echo "<li><a class='btn py-2 mb-2 block' href='/codenotes.php?g=$gameID'><span class='icon icon-md ml-1 mr-3'>📑</span>Code Notes</a></li>";
                $numOpenTickets = countOpenTickets(
                    requestInputSanitized('f') == $unofficialFlag,
                    requestInputSanitized('t', TicketFilters::Default),
                    null,
                    null,
                    null,
                    $gameID
                );
                if ($flagParam == $unofficialFlag) {
                    echo "<li><a class='btn py-2 mb-2 block' href='/ticketmanager.php?g=$gameID&f=$flagParam'><span class='icon icon-md ml-1 mr-3'>🎫</span>Open Unofficial Tickets ($numOpenTickets)</a></li>";
                } else {
                    echo "<li><a class='btn py-2 mb-2 block' href='/ticketmanager.php?g=$gameID'><span class='icon icon-md ml-1 mr-3'>🎫</span>Open Tickets ($numOpenTickets)</a></li>";
                }
            }
            if ($numAchievements == 0) {
                echo "<li><a class='btn py-2 mb-2 block' href='/setRequestors.php?g=$gameID'><span class='icon icon-md ml-1 mr-3'>📜</span>Set Requestors</a></li>";
            }
            echo "</ul>";
        }

        echo "</div>";

        // Progression component (mobile only)
        if ($user !== null && $numAchievements > 0 && $isOfficial) {
            echo "<div class='mb-5 hidden lg:block'>";
            echo $initializedProgressComponent;
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
            RenderGameCompare($user, $gameID, $friendScores, $totalPossible);
        }

        if ($numAchievements > 0) {
            echo "<div id='achdistribution' class='component' >";
            echo "<h2 class='text-h3'>Achievement Distribution</h2>";
            echo "<div id='chart_distribution' class='min-h-[260px]'></div>";
            echo "</div>";

            RenderTopAchieversComponent($user, $gameTopAchievers['HighScores'], $gameTopAchievers['Masters']);
        }

        RenderGameLeaderboardsComponent($lbData, $forumTopicID);
        ?>
    </aside>
<?php endif ?>
<?php RenderContentEnd(); ?>
