<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$maxCount = 50;

$errorCode = seekGET('e');
$count = seekGET('c', $maxCount);
$offset = seekGET('o', 0);
$searchedHash = seekGET('h', null);
if ($offset < 0) {
    $offset = 0;
}

$hashList = getHashList($offset, $count, $searchedHash);
$totalHashes = getTotalHashes();

RenderDocType();
?>

<head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <?php
    RenderSharedHeader($user);
    RenderTitleTag("Hash List", $user);
    RenderGoogleTracking();
    ?>
</head>

<body>

<?php
RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions);
RenderToolbar($user, $permissions);
?>

<div id='mainpage'>

    <div id='fullcontainer'>
        <?php
        RenderErrorCodeWarning('left', $errorCode);

        echo "<h2 class='longheader'>Search</h2>";

        echo "<div class='searchbox longer'>";
        echo "<form action='/latesthasheslinked.php' method='get'>";
        echo "<input size='50' name='h' value='$searchedHash' />";
        echo "&nbsp;&nbsp;";
        echo "<input type='submit' value='Search Hash' />";
        echo "</form>";
        if (is_null($hashList) || !is_null($searchedHash)) {
            echo "<br>";
            echo "<a href='/latesthasheslinked.php'>Return to Lastest Linked Hashes</a>";
        }
        echo "</div>";

        if (!is_null($hashList)) {
            if (is_null($searchedHash)) {
                echo "<h2 class='longheader'>Lastest Linked Hashes</h2>";
            } else {
                echo "<h2 class='longheader'>Search Results</h2>";
            }
            //Create table headers
            echo "<table class='smalltable'><tbody>";
            echo "<th>Hash</th>";
            echo "<th>Game</th>";
            echo "<th>Date Linked</th>";

            $hashCount = 0;

            // Loop through each hash and display its information
            foreach ($hashList as $hash) {
                if ($hashCount++ % 2 == 0) {
                    echo "<tr>";
                } else {
                    echo "<tr class=\"alt\">";
                }

                echo "<td>" . $hash['Hash'] . "</td>";
                echo "<td>";
                echo GetGameAndTooltipDiv($hash['GameID'], $hash['GameTitle'], $hash['GameIcon'], $hash['ConsoleName']);
                echo "</td>";
                echo "<td>" . $hash['DateAdded'] . "</td>";
            }
            echo "</tbody></table>";

            //Add page traversal links
            if (is_null($searchedHash)) {
                echo "<div class='rightalign row'>";
                if ($offset > 0) {
                    $prevOffset = $offset - $maxCount;
                    echo "<a href='/latesthasheslinked.php'>First</a> - ";
                    echo "<a href='/latesthasheslinked.php?o=$prevOffset'>&lt; Previous $maxCount</a> - ";
                }
                if ($hashCount == $maxCount && $offset != ($totalHashes - $maxCount)) {
                    $nextOffset = $offset + $maxCount;
                    echo "<a href='/latesthasheslinked.php?o=$nextOffset'>Next $maxCount &gt;</a>";
                    echo " - <a href='/latesthasheslinked.php?o=" . ($totalHashes - $maxCount) . "'>Last</a>";
                }
                echo "</div>";
            }
        } else {
            echo "<h2 class='longheader'>Search Results</h2>";
            echo "No results found!";
        }
        ?>
    </div>
</div>

<?php RenderFooter(); ?>

</body>
</html>
