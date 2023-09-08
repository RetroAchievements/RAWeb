<?php

use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$maxCount = 50;

$count = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$searchedHash = requestInputSanitized('h');
if ($offset < 0) {
    $offset = 0;
}

$hashList = getHashList($offset, $count, $searchedHash);
$totalHashes = getTotalHashes();

RenderContentStart("Hash List");
?>
<article>
    <?php
    echo "<h2>Search</h2>";

    echo "<div class='searchbox longer'>";
    echo "<form action='/latesthasheslinked.php'>";
    echo "<input size='50' name='h' value='$searchedHash' />";
    echo "&nbsp;&nbsp;";
    echo "<button class='btn'>Search Hash</button>";
    echo "</form>";
    if (empty($hashList) || $searchedHash !== null) {
        echo "<br>";
        echo "<a href='/latesthasheslinked.php'>Return to Lastest Linked Hashes</a>";
    }
    echo "</div>";

    if (!empty($hashList)) {
        if ($searchedHash === null) {
            echo "<h2>Lastest Linked Hashes</h2>";
        } else {
            echo "<h2>Search Results</h2>";
        }
        echo "<table class='table-highlight'><tbody>";

        echo "<tr class='do-not-highlight'>";
        echo "<th>Hash</th>";
        echo "<th>Game</th>";
        echo "<th>Linked by</th>";
        echo "<th>Date Linked</th>";
        echo "</tr>";

        $hashCount = 0;

        foreach ($hashList as $hash) {
            if ($hashCount++ % 2 == 0) {
                echo "<tr>";
            } else {
                echo "<tr class=\"alt\">";
            }

            $gameID = $hash['GameID'];

            echo "<td><a href='/managehashes.php?g=$gameID'>" . $hash['Hash'] . "</a></td>";
            echo "<td>";
            echo gameAvatar($hash);
            echo "</td>";
            echo "<td>";
            if (!empty($hash['User'])) {
                echo userAvatar($hash['User'], icon: false);
            }
            echo "</td>";
            echo "<td>" . $hash['DateAdded'] . "</td>";
        }
        echo "</tbody></table>";

        if ($searchedHash === null) {
            echo "<div class='text-right'>";
            if ($offset > 0) {
                $prevOffset = $offset - $maxCount;
                echo "<a href='/latesthasheslinked.php'>First</a> - ";
                echo "<a href='/latesthasheslinked.php?o=$prevOffset'>&lt; Previous $maxCount</a> - ";
            }
            if ($hashCount == $maxCount && $offset != $totalHashes - $maxCount) {
                $nextOffset = $offset + $maxCount;
                echo "<a href='/latesthasheslinked.php?o=$nextOffset'>Next $maxCount &gt;</a>";
                echo " - <a href='/latesthasheslinked.php?o=" . ($totalHashes - $maxCount) . "'>Last</a>";
            }
            echo "</div>";
        }
    } else {
        echo "<h2>Search Results</h2>";
        echo "No results found!";
    }
    ?>
</article>
<?php RenderContentEnd(); ?>
