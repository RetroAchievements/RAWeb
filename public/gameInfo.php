<?php

use Carbon\Carbon;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\RatingType;
use LegacyApp\Community\Enums\SubscriptionSubjectType;
use LegacyApp\Community\Enums\TicketFilters;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Enums\ImageType;
use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Enums\UserPreference;

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
$matureContentPref = UserPreference::SiteMsgOff_MatureContent;

$officialFlag = AchievementType::OfficialCore;
$unofficialFlag = AchievementType::Unofficial;
$flags = requestInputSanitized('f', $officialFlag, 'integer');
$isOfficial = $flags !== $unofficialFlag;

$defaultSort = 1;
if (isset($user)) {
    $defaultSort = 13;
}
$sortBy = requestInputSanitized('s', $defaultSort, 'integer');

if (!isset($user) && ($sortBy == 3 || $sortBy == 13)) {
    $sortBy = 1;
}

$numAchievements = getGameMetadata($gameID, $user, $achievementData, $gameData, $sortBy, null, $flags, metrics:true);

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
if ($v != 1 && $isFullyFeaturedGame) {
    foreach ($gameHubs as $hub) {
        if ($hub['Title'] == '[Theme - Mature]') {
            if ($userDetails && BitSet($userDetails['websitePrefs'], $matureContentPref)) {
                break;
            }
            $gate = true;
        }
    }
}
?>
<?php if ($gate): ?>
    <?php RenderContentStart($pageTitle) ?>
    <script>
        function disableMatureContentWarningPreference() {
            const isLoggedIn = <?= isset($userDetails) ?>;
            if (!isLoggedIn) {
                throw new Error('Tried to modify settings for an unauthenticated user.');
            }

            const newPreferencesValue = <?= ($userDetails['websitePrefs'] ?? 0) | (1 << $matureContentPref) ?>;
            const gameId = <?= $gameID ?>;

            fetch('/request/user/update-notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: `preferences=${newPreferencesValue}`,
                credentials: 'same-origin'
            }).then(() => {
                window.location = `/game/<?= $gameID ?>`;
            })
        }
    </script>
    <div id='mainpage'>
        <div id='leftcontainer'>
            <div class='navpath'>
                <?= renderGameBreadcrumb($gameData, addLinkToLastCrumb: false) ?>
            </div>
            <h1 class="text-h3"><?= renderGameTitle($pageTitle) ?></h1>
            <h4>WARNING: THIS GAME MAY CONTAIN CONTENT NOT APPROPRIATE FOR ALL AGES.</h4>
            <br/>
            <div id="confirmation">
                Are you sure that you want to view this game?
                <br/>
                <br/>

                <div class="flex flex-col sm:flex-row gap-4 sm:gap-2">
                    <form id='escapeform' action='/gameList.php'>
                        <input type='hidden' name='c' value='<?= $consoleID ?>'/>
                        <input type='submit' class='leading-normal' value='No. Get me out of here.'/>
                    </form>

                    <form id='consentform' action='/game/<?= $gameID ?>'>
                        <input type='hidden' name='v' value='1'/>
                        <input type='submit' class='leading-normal' value='Yes. I&apos;m an adult.'/>
                    </form>

                    <?php if ($userWebsitePrefs): ?>
                        <button
                            class='break-words whitespace-normal leading-normal'
                            onclick='disableMatureContentWarningPreference()'
                        >
                            Yes. And never ask me again for games with mature content.
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php RenderContentEnd(); ?>
    <?php return ?>
<?php endif ?>
<?php
$achDist = null;
$achDistHardcore = null;
$authorInfo = [];
$commentData = null;
$gameTopAchievers = null;
$lbData = null;
$numArticleComments = null;
$numDistinctPlayersCasual = null;
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
$openTickets = 0;
$claimData = null;
$claimListLength = 0;

if ($isFullyFeaturedGame) {
    $numDistinctPlayersCasual = $gameData['NumDistinctPlayersCasual'];
    $numDistinctPlayersHardcore = $gameData['NumDistinctPlayersHardcore'];

    $achDist = getAchievementDistribution($gameID, UnlockMode::Softcore, $user, $flags);
    $achDistHardcore = getAchievementDistribution($gameID, UnlockMode::Hardcore, $user, $flags);

    $numArticleComments = getRecentArticleComments(ArticleType::Game, $gameID, $commentData);

    $numLeaderboards = getLeaderboardsForGame($gameID, $lbData, $user);

    $screenshotWidth = 200;
    $screenshotMaxHeight = 240; // corresponds to the DS screen aspect ratio

    // Quickly calculate earned/potential
    $totalEarnedCasual = 0;
    $totalEarnedHardcore = 0;
    $numEarnedCasual = 0;
    $numEarnedHardcore = 0;
    $totalPossible = 0;

    $totalEarnedTrueRatio = 0;
    $totalPossibleTrueRatio = 0;

    $authorName = [];
    $authorCount = [];
    if (isset($achievementData)) {
        foreach ($achievementData as &$nextAch) {
            // Add author to array if it's not already there and initialize achievement count for that author.
            if (!in_array($nextAch['Author'], $authorName)) {
                $authorName[mb_strtolower($nextAch['Author'])] = $nextAch['Author'];
                $authorCount[mb_strtolower($nextAch['Author'])] = 1;
            } // If author is already in array then increment the achievement count for that author.
            else {
                $authorCount[mb_strtolower($nextAch['Author'])]++;
            }

            $totalPossible += $nextAch['Points'];
            $totalPossibleTrueRatio += $nextAch['TrueRatio'];

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

    // Get the top ten players at this game:
    $gameTopAchievers = getGameTopAchievers($gameID);

    // Determine if the logged in user is the sole author of the set
    if (isset($user)) {
        $isSoleAuthor = checkIfSoleDeveloper($user, $gameID);
    }

    // Get user claim data
    if (isset($user) && $permissions >= Permissions::JuniorDeveloper) {
        $openTickets = countOpenTicketsByDev($user);
    }

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
    <?php RenderOpenGraphMetadata($pageTitle, "game", media_asset($gameData['ImageIcon']), "Game Info for $gameTitle ($consoleName)"); ?>
<?php endif ?>
<?php RenderContentStart($pageTitle); ?>
<?php if ($isFullyFeaturedGame): ?>
    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
    google.load('visualization', '1.0', { 'packages': ['corechart'] });
    google.setOnLoadCallback(drawCharts);

    function drawCharts() {
        var dataTotalScore = new google.visualization.DataTable();

        // Declare columns
        dataTotalScore.addColumn('number', 'Total Achievements Won');
        dataTotalScore.addColumn('number', 'Hardcore Users');
        dataTotalScore.addColumn('number', 'Softcore Users');

        dataTotalScore.addRows([
            <?php
            $largestWonByCount = 0;
            $count = 0;
            $plural = '';
            for ($i = 1; $i <= $numAchievements; $i++) {
                if ($count++ > 0) {
                    $plural = 's';
                    echo ", ";
                }
                $wonByUserCount = $achDist[$i];

                if ($wonByUserCount > $largestWonByCount) {
                    $largestWonByCount = $wonByUserCount;
                }

                echo "[ {v:$i, f:\"Earned $i achievement$plural\"}, $achDistHardcore[$i], $wonByUserCount - $achDistHardcore[$i] ] ";
            }

            if ($largestWonByCount > 20) {
                $largestWonByCount = -2;
            }

            // if there's less than 20 achievements, just show a line for every value
            // otherwise show 10 lines (chart will actually use less lines if it doesn't divide evenly)
            $numGridlines = ($numAchievements < 20) ? $numAchievements : 10;
            ?>
        ]);
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
                minorGridlines: { count: 0 },
                format: '#',
                slantedTextAngle: 90,
                maxAlternation: 0
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
        $.post('/request/set-request/list.php', {
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
        $.post('/request/set-request/update.php', {
            game: gameID,
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

    function ResetProgress() {
        if (confirm('Are you sure you want to reset this progress?')) {
            showStatusMessage('Updating...');

            $.post('/request/user/reset-achievements.php', {
                game: <?= $gameID ?>
            })
                .done(function () {
                    location.reload();
                });
        }
    }
    </script>
<?php endif ?>
<div id="mainpage">
    <div id="<?= $isFullyFeaturedGame ? 'leftcontainer' : 'fullcontainer' ?>">
        <div id="achievement">
            <?php

            if ($isFullyFeaturedGame) {
                echo "<div class='navpath leading-4'>";
                echo renderGameBreadcrumb($gameData, addLinkToLastCrumb: $flags === $unofficialFlag);
                if ($flags === $unofficialFlag) {
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

            $fallBackConsoleIcon = asset("assets/images/system/unknown.png");
            $cleanSystemShortName = Str::lower(str_replace("/", "", config("systems.$consoleID.name_short")));
            $iconName = Str::kebab($cleanSystemShortName);

            echo "<h1 class='text-h3'>";
            echo " <span class='block mb-1'>$renderedTitle</span>";
            echo " <div class='flex items-center gap-x-1'>";
            echo "  <img src='" . asset("assets/images/system/$iconName.png") . "' width='24' height='24' alt='Console icon' onerror='this.src=\"$fallBackConsoleIcon\"'>";
            echo "  <span class='block text-sm tracking-tighter'>$consoleName</span>";
            echo " </div>";
            echo "</h1>";

            echo "<div class='flex flex-col sm:flex-row sm:w-full gap-x-4 gap-y-2 items-center mb-4'>";
            echo "<img class='aspect-1 object-cover rounded-sm w-[96px] h-[96px]' src='$imageIcon' width='96' height='96' alt='$pageTitleAttr'>";

            echo "<div class='flex flex-col w-full gap-1'>";
            if ($isFullyFeaturedGame) {
                RenderMetadataTableRow('Developer', $developer, $gameHubs, ['Hacker']);
                RenderMetadataTableRow('Publisher', $publisher, $gameHubs, ['Hacks']);
                RenderMetadataTableRow('Genre', $genre, $gameHubs, ['Subgenre']);
            } else {
                RenderMetadataTableRow('Developer', $developer);
                RenderMetadataTableRow('Publisher', $publisher);
                RenderMetadataTableRow('Genre', $genre);
            }
            RenderMetadataTableRow('Released', $released);
            echo "</div>";

            echo "</div>";

            if ($isFullyFeaturedGame) {
                echo <<<HTML
                    <div class="mb-3 -mx-5 sm:mx-0 grid sm:flex sm:justify-around sm:w-full gap-y-1 sm:gap-x-5">
                        <div class="flex justify-center items-center">
                            <img class="w-full sm:rounded-sm" src="$imageTitle" alt="Title screenshot">
                        </div>

                        <div class="flex justify-center items-center">
                            <img class="w-full sm:rounded-sm" src="$imageIngame" alt="In-game screenshot">
                        </div>
                    </div>
                HTML;
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

                    if ($flags == $unofficialFlag) {
                        echo "<div><a class='btn btn-link' href='/game/$gameID" . ($v == 1 ? '?v=1' : '') . "'>View Core Achievements</a></div>";
                        echo "<div><a class='btn btn-link' href='/achievementinspector.php?g=$gameID&f=5'>Manage Unofficial Achievements</a></div>";
                    } else {
                        echo "<div><a class='btn btn-link' href='/game/$gameID?f=5" . ($v == 1 ? '&v=1' : '') . "'>View Unofficial Achievements</a></div>";
                        echo "<div><a class='btn btn-link' href='/achievementinspector.php?g=$gameID'>Manage Core Achievements</a></div>";
                    }

                    // Display leaderboard management options depending on the current number of leaderboards
                    if ($numLeaderboards == 0) {
                        echo "<form action='/request/leaderboard/create.php' method='post'>";
                        echo csrf_field();
                        echo "<input type='hidden' name='game' value='$gameID'>";
                        echo "<button class='btn'>Create First Leaderboard</button>";
                        echo "</form>";
                    } else {
                        echo "<div><a class='btn btn-link' href='/leaderboardList.php?g=$gameID'>Manage Leaderboards</a></div>";
                    }

                    if ($permissions >= Permissions::Developer) {
                        echo "<div><a class='btn btn-link' href='/managehashes.php?g=$gameID'>Manage Hashes</a></div>";
                    }

                    if ($permissions >= Permissions::Admin && !$isEventGame) {
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
                        echo "<button>Recalculate True Ratios</button>";
                        echo "</form>";
                    }

                    // Display the claims links if not an event game
                    if (!$isEventGame) {
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
                                :openTickets="$openTickets"
                                :user="$user"
                                :userPermissions="$permissions"
                            />
                        ', [
                            'claimData' => $claimData,
                            'consoleID' => $consoleID,
                            'forumTopicID' => $forumTopicID,
                            'gameID' => $gameID,
                            'gameTitle' => $gameTitle,
                            'isOfficial' => $isOfficial,
                            'isSoleAuthor' => $isSoleAuthor,
                            'numAchievements' => $numAchievements,
                            'openTickets' => $openTickets,
                            'permissions' => $permissions,
                            'user' => $user,
                        ]);
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

                if ($permissions >= Permissions::Admin) {
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
                    echo "<label for='guide_url'>Guide URL</label><input type='url' name='guide_url' id='guide_url' value='" . attributeEscape($guideURL) . "' class='w-full'>";
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
                    echo "<div class='mt-1' style='float: left; clear: left' onmouseover=\"mobileSafeTipEvents.mouseOver({$containername}tooltip)\" onmouseout=\"UnTip()\">";
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
                if ($user !== null && $numAchievements == 0 && $flags == $officialFlag) {
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

                if ($flags == $unofficialFlag) {
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
                if ($user !== null && $flags == $officialFlag && !$isEventGame) {
                    echo "<div>";
                    $claimExpiration = null;
                    $primaryClaim = 1;
                    if ($claimListLength > 0) {
                        echo "Claimed by: ";
                        foreach ($claimData as $claim) {
                            $revisionText = $claim['SetType'] == ClaimSetType::Revision && $primaryClaim ? " (" . ClaimSetType::toString(ClaimSetType::Revision) . ")" : "";
                            $claimExpiration = Carbon::parse($claim['Expiration']);
                            echo userAvatar($claim['User'], icon: false) . $revisionText;
                            if ($claimListLength > 1) {
                                echo ", ";
                            }
                            $claimListLength--;
                            $primaryClaim = 0;
                        }

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
                if ($flags == $unofficialFlag) {
                    echo "There are <b>$numAchievements Unofficial</b> achievements worth <b>" . number_format($totalPossible) . "</b> <span class='TrueRatio'>(" . number_format($totalPossibleTrueRatio) . ")</span> points.<br>";
                } else {
                    echo "There are <b>$numAchievements</b> achievements worth <b>" . number_format($totalPossible) . "</b> <span class='TrueRatio'>(" . number_format($totalPossibleTrueRatio) . ")</span> points.<br>";
                }

                if ($user !== null && $numAchievements > 0) {
                    if ($numEarnedHardcore > 0) {
                        echo "You have earned <b>$numEarnedHardcore</b> HARDCORE achievements, worth <b>" . number_format($totalEarnedHardcore) . "</b> <span class='TrueRatio'>(" . number_format($totalEarnedTrueRatio) . ")</span> points.<br>";
                        if ($numEarnedCasual > 0) { // Some Hardcore earns
                            echo "You have also earned <b> $numEarnedCasual </b> SOFTCORE achievements worth <b>" . number_format($totalEarnedCasual) . "</b> points.<br>";
                        }
                    } elseif ($numEarnedCasual > 0) {
                        echo "You have earned <b> $numEarnedCasual </b> SOFTCORE achievements worth <b>" . number_format($totalEarnedCasual) . "</b> points.<br>";
                    } else {
                        echo "You have not earned any achievements for this game.<br/>";
                    }
                }

                if ($user !== null && $numAchievements > 0) {
                    if ($numEarnedCasual > 0 || $numEarnedHardcore > 0) {
                        echo "<div class='devbox mb-4'>";
                        echo "<span onclick=\"$('#resetboxcontent').toggle(); return false;\">Reset Progress ▼</span>";
                        echo "<div id='resetboxcontent' style='display: none'>";
                        echo "<button class='btn btn-danger' type='button' onclick='ResetProgress()'>Reset your progress for this game</button>";
                        echo "</div></div>";
                    }
                }
                echo "</div>";

                if (isset($user) && $numAchievements > 0 && ($numEarnedCasual > 0 || $numEarnedHardcore > 0)) {
                    echo "<div class='mb-2 w-full md:mb-4 lg:max-w-[160px]'>";
                    RenderGameProgress($numAchievements, $numEarnedCasual, $numEarnedHardcore);
                    echo "</div>";
                }

                echo "</div>";

                /*
                if( $user !== NULL && $numAchievements > 0 ) {
                    $renderRatingControl('Achievements Rating', 'ratingach', 'ratingachlabel', $gameRating[RatingType::Achievement]);
                }
                */

                if ($numAchievements > 1) {
                    RenderGameSort($isFullyFeaturedGame, $flags, $officialFlag, $gameID, $sortBy);
                }

                echo "<table class='achievementlist table-highlight'><tbody>";

                if (isset($achievementData)) {
                    for ($i = 0; $i < 2; $i++) {
                        if ($i == 0 && $numEarnedCasual == 0 && $numEarnedHardcore == 0) {
                            continue;
                        }

                        foreach ($achievementData as &$nextAch) {
                            $achieved = (isset($nextAch['DateEarned']));

                            if ($i == 0 && $achieved == false) {
                                continue;
                            }
                            if ($i == 1 && $achieved == true) {
                                continue;
                            }

                            $achID = $nextAch['ID'];
                            $achTitle = $nextAch['Title'];
                            $achDesc = $nextAch['Description'];
                            $achAuthor = $nextAch['Author'];
                            $achPoints = $nextAch['Points'];
                            $achTrueRatio = $nextAch['TrueRatio'];
                            $dateAch = "";
                            if ($achieved) {
                                $dateAch = $nextAch['DateEarned'];
                            }
                            $achBadgeName = $nextAch['BadgeName'];

                            sanitize_outputs(
                                $achTitle,
                                $achDesc,
                            );

                            $earnedOnHardcore = isset($nextAch['DateEarnedHardcore']);

                            $imgClass = $earnedOnHardcore ? 'goldimagebig' : 'badgeimg';

                            $wonBy = $nextAch['NumAwarded'];
                            $wonByHardcore = $nextAch['NumAwardedHardcore'];
                            if ($numDistinctPlayersCasual == 0) {
                                $completionPctCasual = "0";
                                $completionPctHardcore = "0";
                            } else {
                                $completionPctCasual = sprintf("%01.2f", ($wonBy / $numDistinctPlayersCasual) * 100);
                                $completionPctHardcore = sprintf("%01.2f", ($wonByHardcore / $numDistinctPlayersCasual) * 100);
                            }

                            if ($user == "" || !$achieved) {
                                $achBadgeName .= "_lock";
                            }

                            echo "<tr>";
                            echo "<td>";

                            echo "<div class='flex justify-between gap-3 items-start'>";

                            echo "<div>";

                            $nextAch['Unlock'] = $earnedOnHardcore ? '<br clear=all>Unlocked: ' . getNiceDate(strtotime($nextAch['DateEarnedHardcore'])) . '<br>HARDCORE' : null;
                            echo achievementAvatar(
                                $nextAch,
                                label: false,
                                icon: $achBadgeName,
                                iconSize: 64,
                                iconClass: $imgClass,
                                tooltip: false,
                            );
                            echo "</div>";

                            echo "<div class='md:flex justify-between items-start gap-3 grow'>";
                            $pctAwardedCasual = 0;
                            $pctAwardedHardcore = 0;
                            $pctComplete = 0;
                            if ($numDistinctPlayersCasual) {
                                $pctAwardedCasual = $wonBy / $numDistinctPlayersCasual;
                                $pctAwardedHardcore = $wonByHardcore / $numDistinctPlayersCasual;
                                $pctAwardedHardcoreProportion = 0;
                                if ($wonByHardcore > 0 && $wonBy > 0) {
                                    $pctAwardedHardcoreProportion = $wonByHardcore / $wonBy;
                                }

                                $pctAwardedCasual = sprintf("%01.2f", $pctAwardedCasual * 100.0);
                                $pctAwardedHardcore = sprintf("%01.2f", $pctAwardedHardcoreProportion * 100.0);

                                $pctComplete = sprintf(
                                    "%01.2f",
                                    ($wonBy + $wonByHardcore) * 100.0 / $numDistinctPlayersCasual
                                );
                            }
                            echo "<div class='achievementdata'>";
                            echo "<div class='mb-1 lg:mt-1'>";
                            echo achievementAvatar(
                                $nextAch,
                                label: true,
                                icon: false,
                                tooltip: false,
                            );
                            echo " <span class='TrueRatio'>($achTrueRatio)</span>";
                            echo "</div>";
                            echo "<div class='mb-2'>$achDesc</div>";
                            if ($flags != $officialFlag && isset($user) && $permissions >= Permissions::JuniorDeveloper) {
                                echo "<div class='text-2xs'>Author: " . userAvatar($achAuthor, icon: false) . "</div>";
                            }
                            if ($achieved) {
                                echo "<div class='date smalltext'>Unlocked $dateAch</div>";
                            }
                            echo "</div>";

                            echo "<div class='my-2 flex flex-col items-center text-center whitespace-nowrap'>";
                            echo "<div class='progressbar w-full md:w-60 lg:w-40'>";
                            echo "<div class='completion' style='width:$pctAwardedCasual%'>";
                            echo "<div class='completion-hardcore' style='width:$pctAwardedHardcore%'></div>";
                            echo "</div>";
                            echo "</div>";
                            echo "<div class='mt-1 text-2xs'>";
                            if ($wonByHardcore > 0) {
                                echo "<p>" . number_format($wonBy) . " <strong>(" . number_format($wonByHardcore) . ")</strong> of " . number_format($numDistinctPlayersCasual) . "</p>";
                            } else {
                                echo "<p>" . number_format($wonBy) . " of " . number_format($numDistinctPlayersCasual) . "</p>";
                            }
                            echo "<p class='text-2xs'>$pctAwardedCasual% unlock rate</p>";
                            echo "</div>";
                            echo "</div>";

                            echo "</div>";

                            echo "</div>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                }
                echo "</tbody></table>";
            }

            if (!$isFullyFeaturedGame) {
                if (!empty($relatedGames)) {
                    RenderGameSort($isFullyFeaturedGame, $flags, $officialFlag, $gameID, $sortBy);
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
    </div>
    <?php if ($isFullyFeaturedGame): ?>
        <div id="rightcontainer">
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
                    if ($flags == $unofficialFlag) {
                        echo "<li><a class='btn py-2 mb-2 block' href='/ticketmanager.php?g=$gameID&f=$flags'><span class='icon icon-md ml-1 mr-3'>🎫</span>Open Unofficial Tickets ($numOpenTickets)</a></li>";
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

            if (!empty($gameSubsets)) {
                RenderGameAlts($gameSubsets, 'Subsets');
            }

            if (!empty($gameAlts)) {
                RenderGameAlts($gameAlts, 'Similar Games');
            }

            if (!empty($gameHubs)) {
                RenderGameAlts($gameHubs, 'Collections');
            }

            if ($user !== null && $numAchievements > 0) {
                RenderGameCompare($user, $gameID, $friendScores, $totalPossible);
            }

            if ($numAchievements > 0) {
                echo "<div id='achdistribution' class='component' >";
                echo "<h2 class='text-h3'>Achievement Distribution</h2>";
                echo "<div id='chart_distribution'></div>";
                echo "</div>";

                RenderTopAchieversComponent($user, $gameTopAchievers['HighScores'], $gameTopAchievers['Masters']);
            }

            RenderGameLeaderboardsComponent($lbData, $forumTopicID);
            ?>
        </div>
    <?php endif ?>
</div>
<?php RenderContentEnd(); ?>
