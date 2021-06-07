<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

use RA\Permissions;

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$gameID = requestInputSanitized('g', 1, 'integer');
$gameData = getGameData($gameID);

sanitize_outputs(
    $gameData['Title'],
    $gameData['ConsoleName'],
);

getCodeNotes($gameID, $codeNotes);

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead('Code Notes');
?>
<body>
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

