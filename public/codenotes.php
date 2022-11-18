<?php

use RA\Permissions;

authenticateFromCookie($user, $permissions, $userDetails);

$gameID = requestInputSanitized('g', 1, 'integer');
$gameData = getGameData($gameID);

sanitize_outputs(
    $gameData['Title'],
    $gameData['ConsoleName'],
);

getCodeNotes($gameID, $codeNotes);

RenderContentStart('Code Notes');
?>
<div id='mainpage'>
    <div id="fullcontainer">
        <?php echo "Game: " . gameAvatar($gameData); ?>
        <?php
        if (isset($gameData) && isset($user) && $permissions >= Permissions::Registered) {
            RenderCodeNotes($codeNotes, true);
        }
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
