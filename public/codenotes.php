<?php

use LegacyApp\Site\Enums\Permissions;

authenticateFromCookie($user, $permissions, $userDetails);

$gameID = requestInputSanitized('g', 1, 'integer');
$gameData = getGameData($gameID);

sanitize_outputs(
    $gameData['Title'],
    $gameData['ConsoleName'],
);

getCodeNotes($gameID, $codeNotes);

RenderContentStart('Code Notes - ' . $gameData['Title']);
?>
<div id='mainpage'>
    <div id="fullcontainer">
        <div class='navpath'>
            <?= renderGameBreadcrumb($gameData) ?>
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
