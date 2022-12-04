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
        <div class='navpath'>
            <a href='/gameList.php'>All Games</a>
            &raquo; <a href='/gameList.php?c=<?= $gameData['ConsoleID'] ?>'><?= $gameData['ConsoleName'] ?></a>
            &raquo; <a href='/game/<?= $gameID ?>'><?= $gameData['Title'] ?></a>
            &raquo; <b>Code Notes</b>
        </div>
        <h3>Code Notes</h3>
        <?= gameAvatar($gameData, iconSize: 64); ?>
        <br/>
        <br/>
        <p>The RetroAchievements addressing scheme for most systems is to access the system memory
        at address $00000000, immediately followed by the cartridge memory. As such, the addresses
        displayed below may not directly correspond to the addresses on the real hardware.</p>
        <br/>
        <?php
        if (isset($gameData) && isset($user) && $permissions >= Permissions::Registered) {
            RenderCodeNotes($codeNotes);
        }
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
