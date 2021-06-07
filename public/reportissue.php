<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);
$cookieRaw = RA_ReadCookie('RA_Cookie');

$achievementID = requestInputSanitized('i', 0, 'integer');

$dataOut = null;
if ($achievementID == 0 ||
    getAchievementMetadata($achievementID, $dataOut) == false) {
    header("Location: " . getenv('APP_URL') . "?e=unknownachievement");
    exit;
}

$emulators = getActiveEmulatorReleases();

$achievementTitle = $dataOut['AchievementTitle'];
$desc = $dataOut['Description'];
$gameTitle = $dataOut['GameTitle'];
$achPoints = $dataOut['Points'];
$achBadgeName = $dataOut['BadgeName'];
$consoleID = $dataOut['ConsoleID'];
$consoleName = $dataOut['ConsoleName'];
$gameID = $dataOut['GameID'];
$gameBadge = $dataOut['GameIcon'];

sanitize_outputs(
    $achievementTitle,
    $gameTitle,
    $consoleName
);

$errorCode = requestInputSanitized('e');

RenderHtmlStart(true);
RenderHtmlHead("Report Broken Achievement");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode); ?>
<?php RenderToolbar($user, $permissions); ?>
<script type="text/javascript">
  function displayCore() {
    if (['RetroArch', 'RALibRetro'].indexOf(document.getElementById('emulator').value) > -1) {
      document.getElementById('core-row').style.display = '';
    } else {
      document.getElementById('core-row').style.display = 'none';
    }
  }
</script>
<div id="mainpage">
    <div id="fullcontainer">
        <div class="navpath">
            <a href="/gameList.php">All Games</a>
            &raquo; <a href="/gameList.php?c=<?php echo $consoleName ?>"><?php echo $consoleName ?></a>
            &raquo; <a href="/game/<?php echo $gameID ?>"><?php echo $gameTitle ?></a>
            &raquo; <a href="/achievement/<?php echo $achievementID ?>"><?php echo $achievementTitle ?></a>
            &raquo; <b>Issue Report</b>
        </div>

        <h3 class="longheader">Report Broken Achievement</h3>

        <form action="/request/ticket/create.php" method="post">
            <input type="hidden" value="<?php echo $user ?>" name="u">
            <input type="hidden" value="<?php echo $cookieRaw ?>" name="c">
            <input type="hidden" value="<?php echo $achievementID ?>" name="i">
            <table>
                <tbody>
                <tr>
                    <td>Game:</td>
                    <td style="width:80%">
                        <?php echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameBadge, $consoleName, false) ?>
                    </td>
                </tr>
                <tr>
                    <td>Achievement:</td>
                    <td>
                        <?php echo GetAchievementAndTooltipDiv(
    $achievementID,
    $achievementTitle,
    $desc,
    $achPoints,
    $gameTitle,
    $achBadgeName,
    true
) ?>
                    </td>
                </tr>
                <tr class="alt">
                    <td><label for="issue">Issue:</label></td>
                    <td>
                        <select name="p" id="issue" required>
                            <option value="" disabled selected hidden>Select your issue...</option>
                            <option value="1">Triggered at wrong time</option>
                            <option value="2">Doesn't Trigger</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="emulator">Emulator:</label></td>
                    <td>
                        <select name="note[emulator]" id="emulator" onchange="displayCore()" required>
                            <option value="" disabled selected hidden>Select your emulator...</option>
                            <?php foreach ($emulators as $emulator): ?>
                                <option><?= $emulator['handle'] ?></option>
                            <?php endforeach ?>
                        </select>
                    </td>
                </tr>
                <tr id="core-row" style="display: none">
                    <td>
                        <label for="core">Core:</label>
                    </td>
                    <td>
                        <input type="text" name="note[core]" id="core" placeholder="Which core did you use?"
                               style="width:100%;margin-top: 3px">
                    </td>
                </tr>
                <tr>
                    <td><label for="checksum">Checksum:</label></td>
                    <td>
                        <select name="note[checksum]" id="checksum" required>
                            <option value="Unknown">I don't know.</option>
                            <?php
                            foreach (getHashListByGameID($gameID) as $listKey => $hashArray) {
                                foreach ($hashArray as $hashKey => $hash) {
                                    echo "<option value='$hash'>$hash</option>";
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="description">Description:</label></td>
                    <td colspan="2">
                        <textarea class="requiredinput fullwidth forum" name="note[description]" id="description"
                                  style="height:160px" rows="5" cols="61" placeholder="Describe your issue here..."
                                  required></textarea>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="2" class="fullwidth">
                        <input style="float:right" type="submit" value="Submit Issue Report" size="37">
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
