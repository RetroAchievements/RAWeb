<?php

use App\Platform\Models\System;
use Illuminate\Support\Facades\Blade;

authenticateFromCookie($user, $permissions, $userDetails);

$maxCount = 50;

$consoleList = System::get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name']);

// Remove 'Hubs' and 'Events' from the collection.
$consolesToRemove = ['Hubs', 'Events'];
$consoleList = $consoleList->filter(function ($value, $key) use ($consolesToRemove) {
    return !in_array($value, $consolesToRemove);
});

$consoleList = $consoleList->sort();
$consoleList->prepend('All Consoles', 0);

$count = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');
$method = requestInputSanitized('p', 0, 'integer');
$consoleID = requestInputSanitized('i', 0, 'integer');

$gameData = getGameListSearch($offset, $count, $method, $consoleID);

RenderContentStart("Hardest Games");
?>
<article>
    <?php
    echo "<div class='navpath'>";
    echo "<b>Hardest Games</b>";    // NB. This will be a stub page
    echo "</div>";

    echo "<div class='detaillist'>";

    echo "<h3>Hardest Games</h3>";

    echo "<div class='w-full flex flex-col sm:flex-row sm:items-center lg:items-start gap-2 justify-between'>";
    echo "<p>Showing games by most total RetroPoints</p>";

    echo "<div class='flex items-center gap-x-2'>";
    echo "<p>Show:</p>";
    echo "<select class='w-full sm:w-auto' onchange='window.location = \"/gameSearch.php?o=0&p=$method&i=\" + this.options[this.selectedIndex].value'>";
    foreach ($consoleList as $nextConsoleID => $nextConsoleName) {
        // 0 is "All Consoles". Don't show consoles that haven't been rolled out yet.
        if ($nextConsoleID == 0 || isValidConsoleId($nextConsoleID)) {
            sanitize_outputs($nextConsoleName);
            echo "<option value='$nextConsoleID' " . ($nextConsoleID == $consoleID ? "selected" : "") . ">$nextConsoleName</option>";
        }
    }
    echo "</select>";
    echo "</div>";

    echo "</div>";

    // echo "Show: | ";

    // if( $method==0 ) 	echo "by number of awards given";
    // else 				echo "<a href='/popularGames.php?p=0'>by number of awards given</a>";

    // echo " | ";

    // if( $method==1 )	echo "by unique members played ";
    // else				echo "<a href='/popularGames.php?p=1'>by unique members played</a> ";

    // echo " | ";

    echo "<table class='table-highlight'><tbody>";

    echo "<tr class='do-not-highlight'>";
    echo "<th>Rank</th>";
    echo "<th>Game</th>";
    echo "<th>Genre</th>";
    echo "<th>Publisher</th>";
    echo "<th>Developer</th>";
    echo "<th>Total RetroPoints</th>";
    echo "</tr>";

    // $countCol = ( $method == 0 ) ? "Awards Given" : "Played By";
    // echo "<th>$countCol</th>";

    $count = 0;

    foreach ($gameData as $gameEntry) {
        $count++;
        $gameID = $gameEntry['ID'];
        $gameTitle = $gameEntry['Title'];
        $gameForumTopicID = $gameEntry['ForumTopicID'];
        $gameFlags = $gameEntry['Flags'];
        $gameIcon = $gameEntry['ImageIcon'];
        $gamePublisher = $gameEntry['Publisher'];
        $gameDeveloper = $gameEntry['Developer'];
        $gameGenre = $gameEntry['Genre'];
        $gameReleased = $gameEntry['Released'];
        $gameTA = $gameEntry['TotalTruePoints'];
        $consoleName = $gameEntry['ConsoleName'];
        // $numRecords = $gameEntry['NumRecords'];

        sanitize_outputs(
            $gameTitle,
            $consoleName
        );

        echo "<tr>";

        echo "<td>";
        echo $count + $offset;
        echo "</td>";

        echo "<td class='py-2.5' style='min-width:30%'>";
        echo Blade::render('
            <x-game.multiline-avatar
                :gameId="$gameId"
                :gameTitle="$gameTitle"
                :gameImageIcon="$gameImageIcon"
                :consoleName="$consoleName"
            />
        ', [
            'gameId' => $gameEntry['ID'],
            'gameTitle' => $gameEntry['Title'],
            'gameImageIcon' => $gameEntry['ImageIcon'],
            'consoleName' => $gameEntry['ConsoleName'],
        ]);
        echo "</td>";

        echo "<td>";
        echo "$gameGenre";
        echo "</td>";

        echo "<td>";
        echo "$gamePublisher";
        echo "</td>";

        echo "<td>";
        echo "$gameDeveloper";
        echo "</td>";

        echo "<td>";
        echo localized_number($gameTA);
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "<div class='text-right'>";
    if ($offset > 0) {
        $prevOffset = $offset - $maxCount;
        echo "<a href='/gameSearch.php?o=$prevOffset&amp;p=$method&amp;i=$consoleID'>&lt; Previous $maxCount</a> - ";
    }
    if ($count == $maxCount) {
        // Max number fetched, i.e. there are more. Can goto next 25.
        $nextOffset = $offset + $maxCount;
        echo "<a href='/gameSearch.php?o=$nextOffset&amp;p=$method&amp;i=$consoleID'>Next $maxCount &gt;</a>";
    }
    echo "</div>";
    ?>
</article>
<?php RenderContentEnd(); ?>
