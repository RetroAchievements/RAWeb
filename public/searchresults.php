<?php

use App\Platform\Models\Achievement;
use App\Site\Enums\SearchType;

authenticateFromCookie($user, $permissions, $userDetails);

$searchQuery = request()->input('s') ?? '';
$searchType = requestInputSanitized('t', SearchType::All, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$maxCount = 50;

if (!SearchType::isValid($searchType)) {
    $searchType = SearchType::All;
}

if (!canSearch($searchType, $permissions)) {
    abort(403);
}

$searchResults = [];
$resultsCount = 0;
if (strlen($searchQuery) >= 2) {
    $resultsCount = performSearch($searchType, $searchQuery, $offset, $maxCount, $permissions, $searchResults);
}

RenderContentStart("Search");
?>
<article>
    <?php
    echo "<div class='navpath'>";
    echo "<b>Search</b></a>";
    echo "</div>";

    echo "<h2>Search</h2>";

    echo "<div class='searchbox longer'>";
    echo "<form action='/searchresults.php'>";
    // echo "Search:&nbsp;";
    $searchQueryEscaped = attributeEscape($searchQuery);
    echo "<input size='42' name='s' type='text' value='$searchQueryEscaped' placeholder='Search the site...' />";
    echo " in ";
    echo "<select name='t'>";
    foreach (SearchType::cases() as $t) {
        if (!canSearch($t, $permissions)) {
            continue;
        }

        if ($t == $searchType) {
            echo "<option value='$t' selected>";
        } else {
            echo "<option value='$t'>";
        }
        echo SearchType::toString($t);
        echo "</option>";
    }
    echo "</select>";
    echo "&nbsp;&nbsp;";
    echo "<button class='btn'>Search</button>";
    echo "</form>";
    echo "</div>";

    if ($searchQuery !== null) {
        echo "<h4>Results:</h4>";
        if ($resultsCount == 0) {
            echo "No results found!";
        } else {
            echo "<table class='table-highlight'><tbody>";
            echo "<tr class='do-not-highlight'>";
            echo "<th>Type</th>";
            echo "<th colspan='2'>Match</th>";
            echo "</tr>";
            $lastType = '';
            $iter = 0;
            foreach ($searchResults as $nextResult) {
                $nextType = $nextResult['Type'];
                $nextID = $nextResult['ID'];
                $nextTarget = $nextResult['Target'];
                $nextTitle = attributeEscape(strip_tags($nextResult['Title']));

                if ($nextType !== $lastType) {
                    $lastType = $nextType;
                }

                echo "<tr>";

                switch ($nextType) {
                    case SearchType::User:
                        echo "<td>User</td>";
                        echo "<td colspan='2'>";
                        echo userAvatar($nextID);
                        echo "</td>";
                        break;

                    case SearchType::Achievement:
                        echo "<td>Achievement</td>";
                        echo "<td colspan='2'>";
                        /** @var ?Achievement $achievement */
                        $achievement = Achievement::find($nextID);
                        echo achievementAvatar($achievement);
                        echo "</td>";
                        break;

                    case SearchType::Game:
                        echo "<td>Game</td>";
                        $gameData = getGameData($nextID);
                        echo "<td colspan='2'>";
                        echo gameAvatar($gameData);
                        echo "</td>";
                        break;

                    case SearchType::Forum:
                        echo "<td>Forum Comment</td>";
                        echo "<td>";
                        echo userAvatar($nextID);
                        echo "</td>";
                        echo "<td><a href='$nextTarget'>$nextTitle</a></td>";
                        break;

                    default:
                        echo "<td>" . substr(SearchType::toString($nextType), 0, -1) . "</td>";
                        echo "<td>";
                        echo userAvatar($nextID);
                        echo "</td>";
                        echo "<td><a href='$nextTarget'>$nextTitle</a></td>";
                        break;
                }

                echo "</tr>";
            }

            echo "</tbody></table>";

            echo "<div class='text-right'>";
            RenderPaginator($resultsCount, $maxCount, $offset, "/searchresults.php?s=$searchQueryEscaped&t=$searchType&o=");
            echo "</div>";
        }
    }
    ?>
</article>
<?php RenderContentEnd(); ?>
