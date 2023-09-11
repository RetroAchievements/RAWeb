<?php

use App\Community\Enums\UserGameListType;
use App\Community\Models\UserGameListEntry;
use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    abort(401);
}

$gameID = requestInputSanitized('g', null, 'integer');
if (empty($gameID)) {
    abort(404);
}

$gameData = getGameData($gameID);
if ($gameData === null) {
    abort(404);
}

if ($permissions < Permissions::Moderator && !hasSetClaimed($user, $gameID, true)) {
    abort(401);
}

$listUsers = UserGameListEntry::where('type', UserGameListType::Develop)
    ->where('GameID', $gameID)
    ->join('UserAccounts', 'UserAccounts.ID', '=', 'SetRequest.user_id')
    ->orderBy('UserAccounts.User')
    ->get();

RenderContentStart($gameData['Title'] . " - Developer Interest");
?>
<article>
    <h2>List of Interested Users</h2>
    <?php
    echo gameAvatar($gameData, iconSize: 96);
    echo "<br><br>";
    echo "The following users have added this game to their Want to Develop list:<br><br>";
    echo "<ul>";
    foreach ($listUsers as $entry) {
        echo "<code><li>" . userAvatar($entry['User']) . "</code></li>";
    }
    echo "</ul>";
    echo "<br>";
    ?>
    <br>
</article>
<?php RenderContentEnd(); ?>
