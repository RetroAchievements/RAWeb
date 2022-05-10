<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ValidateCookie($user, $permissions, $userDetails);
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
<?php RenderHeader($userDetails); ?>
<script>
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
            <input type="hidden" value="<?php echo $achievementID ?>" name="i">
            <table>
                <tbody>
                <tr>
                    <td>Game</td>
                    <td style="width:80%">
                        <?php echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameBadge, $consoleName, false) ?>
                    </td>
                </tr>
                <tr>
                    <td>Achievement</td>
                    <td>
                        <?php echo GetAchievementAndTooltipDiv(
                            $achievementID,
                            $achievementTitle,
                            $desc,
                            $achPoints,
                            $gameTitle,
                            $achBadgeName,
                            true)
                        ?>
                    </td>
                </tr>
                <tr class="alt">
                    <td><label for="issue">Issue</label></td>
                    <td>
                        <select name="p" id="issue" required>
                            <option value="" disabled selected hidden>Select your issue...</option>
                            <option value="1">Triggered at wrong time</option>
                            <option value="2">Doesn't Trigger</option>
                        </select>
                        <a href="/views/issueDescriptionModal.html" rel="modal:open">?</a>
                    </td>
                </tr>
                <tr>
                    <td><label for="emulator">Emulator</label></td>
                    <td>
                        <select name="note[emulator]" id="emulator" required data-bind="value: emulatorValue">
                            <option value="" disabled selected hidden>Select your emulator...</option>
                            <?php foreach ($emulators as $emulator): ?>
                                <option><?= $emulator['handle'] ?></option>
                            <?php endforeach ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="version">Emulator Version</label></td>
                    <td>
                        <input type="text" name="note[emulatorVersion]" id="version" required />
                        <a href="/views/versionDescriptionModal.html" rel="modal:open">Why?</a>
                    </td>
                </tr>
                <tr id="core-row">
                    <td>
                        <label for="core">Core</label>
                    </td>
                    <td>
                        <input type="text" name="note[core]" id="core" placeholder="Which core did you use?"
                               style="width:100%;margin-top: 3px">
                    </td>
                </tr>
                <tr>
                    <td><label for="mode">Mode:</label></td>
                    <td>
                        <select name="m" id="mode" required>
                            <option value="" disabled selected hidden>Soft/Hardcore?</option>
                            <option value="1">Softcore</option>
                            <option value="2">Hardcore</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="checksum">RetroAchievements Hash</label></td>
                    <td>
                        <select name="note[checksum]" id="checksum" required>
                            <option value="Unknown">I don't know.</option>
                            <?php
                            $hashes = getHashListByGameID($gameID);
                            foreach ($hashes as $hashData) {
                                if (!empty($hashData['Name'])) {
                                    $hash = $hashData['Hash'];
                                    echo "<option value='$hash'>$hash - " . $hashData['Name'] . "</option>";
                                }
                            }
                            foreach ($hashes as $hashData) {
                                if (empty($hashData['Name'])) {
                                    $hash = $hashData['Hash'];
                                    echo "<option value='$hash'>$hash</option>";
                                }
                            }
                            ?>
                        </select>
                        <a href="/views/checksumDescriptionModal.html" rel="modal:open">?</a>
                    </td>
                </tr>
                <tr>
                    <td><label for="description">Description</label></td>
                    <td colspan="2">
                        <textarea class="requiredinput fullwidth forum" name="note[description]" id="description"
                                  style="height:160px" rows="5" cols="61" placeholder="Describe your issue here..."
                                  required data-bind="textInput: description"></textarea>
                        <p data-bind="visible: descriptionIsUnhelpful">Please be more specific with your issue&mdash;such as by adding specific reproduction steps or what you did before encountering it&mdash;instead of simply stating that it doesn't work. The more specific, the better.</p>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="2" class="fullwidth">
                        <input style="float:right" type="submit" value="Submit Issue Report" size="37" data-bind="disable: descriptionIsUnhelpful" />
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
<script type="text/javascript">
    let ReportViewModel = function() {
        this.description = ko.observable('');
        this.emulatorValue = ko.observable();
        this.emulatorValue.subscribe(function() {
            displayCore();
        });

        this.descriptionIsUnhelpful = ko.pureComputed(function() {
            let unhelpfulRegex = /(n'?t|not?).*work/ig;
            return this.description().length < 25 && unhelpfulRegex.test(this.description());
        }, this);
    }

    ko.applyBindings(new ReportViewModel());
</script>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
