<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RA\Permissions;

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$gameID = requestInputSanitized('g', 1);
$gameData = getGameData($gameID);
getCodeNotes($gameID, $codeNotes);

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead('Code Notes');
?>
<body>
<script type='text/javascript' src="js/ping_chat.js"></script>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id='mainpage'>
    <div id="fullcontainer">
        <?php echo "Game: " . GetGameAndTooltipDiv($gameData['ID'], $gameData['Title'], $gameData['ImageIcon'], $gameData['ConsoleName']); ?>
        <?php
        if (isset($gameData) && isset($user) && $permissions >= Permissions::Developer) {
            RenderCodeNotes($codeNotes);
        }
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

