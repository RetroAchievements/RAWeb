<?php

use App\Site\Models\User;
use Illuminate\Support\Facades\Blade;

$achievementID = requestInputSanitized('i', 0, 'integer');

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return redirect(route('achievement.show', $achievementID));
}

if ($achievementID == 0) {
    abort(404);
}
$dataOut = GetAchievementData($achievementID);
if (empty($dataOut)) {
    abort(404);
}

/** @var User $userModel */
$userModel = request()->user();
$ticketID = getExistingTicketID($userModel, $achievementID);
if ($ticketID !== 0) {
    return redirect(url("/ticketmanager.php?i=$ticketID"))->withErrors(__('legacy.error.ticket_exists'));
}

$emulators = getActiveEmulatorReleases();

$achievementTitle = $dataOut['Title'];
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

RenderContentStart("Report Broken Achievement");
?>
<script>
function displayCore() {
    if (['RetroArch', 'RALibRetro'].indexOf(document.getElementById('emulator').value) > -1) {
        document.getElementById('core-row').style.display = '';
    } else {
        document.getElementById('core-row').style.display = 'none';
    }
}
</script>
<article>
    <div class="navpath">
        <?= renderGameBreadcrumb($dataOut) ?>
        &raquo; <a href="/achievement/<?= $achievementID ?>"><?=
            renderAchievementTitle($achievementTitle, tags: false) ?></a>
        &raquo; <b>Issue Report</b>
    </div>

    <h3 class="longheader">Report Broken Achievement</h3>

    <form action="/request/ticket/create.php" method="post">
        <?= csrf_field() ?>
        <input type="hidden" value="<?= $achievementID ?>" name="achievement">
        <table class='table-highlight'>
            <tbody>
            <tr>
                <td>Game</td>
                <td style="width:80%">
                    <?= gameAvatar($dataOut) ?>
                </td>
            </tr>
            <tr>
                <td>Achievement</td>
                <td>
                    <?= achievementAvatar($dataOut) ?>
                </td>
            </tr>
            <tr class="alt">
                <td><label for="issue">Issue</label></td>
                <td>
                    <select name="issue" id="issue" required>
                        <option value="" <?= empty(old('issue')) ? 'selected' : '' ?> disabled hidden>Select your issue...</option>
                        <option value="1" <?= old('issue') === '1' ? 'selected' : '' ?>>Triggered at wrong time</option>
                        <option value="2" <?= old('issue') === '2' ? 'selected' : '' ?>>Doesn't Trigger</option>
                    </select>

                    <?=
                        Blade::render('
                            <x-modal-trigger buttonLabel="What do these mean?" modalTitleLabel="Issue Kinds">
                                <x-modal-content.issue-description />
                            </x-modal-trigger>
                        ')
                    ?>
                </td>
            </tr>
            <tr>
                <td><label for="emulator">Emulator</label></td>
                <td>
                    <select name="emulator" id="emulator" required data-bind="value: emulator">
                        <option <?= empty(old('emulator')) ? 'selected' : '' ?> disabled hidden>Select your emulator...</option>
                        <?php foreach ($emulators as $emulator): ?>
                            <?php if (array_key_exists($consoleID, $emulator['systems'])): ?>
                                <option value="<?= $emulator['handle'] ?>" <?= old('emulator') === $emulator['handle'] ? 'selected' : '' ?>><?= $emulator['handle'] ?></option>
                            <?php endif ?>
                        <?php endforeach ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="emulator_version">Emulator Version</label></td>
                <td>
                    <input type="text" name="emulator_version" id="emulator_version" required
                           placeholder="Emulator version"
                           value="<?= old('emulator_version') ?>"
                    >

                    <?=
                        Blade::render('
                            <x-modal-trigger buttonLabel="Why?" modalTitleLabel="Why do I need this?">
                                <x-modal-content.why-emulator-version />
                            </x-modal-trigger>
                        ')
                    ?>
                </td>
            </tr>
            <tr id="core-row">
                <td>
                    <label for="core">Core</label>
                </td>
                <td>
                    <input class="w-full" type="text" name="core" id="core"
                           placeholder="Which core did you use?"
                           value="<?= old('core') ?>"
                    >
                </td>
            </tr>
            <tr>
                <td><label for="mode">Mode:</label></td>
                <td>
                    <select name="mode" id="mode" required>
                        <option value="0" <?= old('mode') === '0' ? 'selected' : '' ?>>Softcore</option>
                        <option value="1" <?= old('mode') === '1' ? 'selected' : '' ?>>Hardcore</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="hash">Supported Game File Hash</label></td>
                <td>
                    <?php
                    $hashes = collect(getHashListByGameID($gameID))
                        ->sortBy('Name')
                        ->groupBy(fn (array $hashData) => (int) empty($hashData['Name']))
                        ->reverse()
                        ->flatten(1);
                    ?>
                    <select name="hash" id="hash" required>
                        <option value="Unknown">I don't know.</option>
                        <?php
                        foreach ($hashes as $hashData) {
                            $hash = $hashData['Hash'];
                            $label = $hash . ' ' . (!empty($hashData['Name']) ? ' - ' . $hashData['Name'] : '');
                            echo "<option value='$hash'" . (old('hash') === $hash ? 'selected' : '') . ">$label</option>";
                        }
                        ?>
                    </select>

                    <?=
                        Blade::render('
                            <x-modal-trigger buttonLabel="How do I find this?" modalTitleLabel="Find the Game File Hash">
                                <x-modal-content.how-to-find-hash />
                            </x-modal-trigger>
                        ')
                    ?>
                </td>
            </tr>
            <tr>
                <td><label for="description">Description</label></td>
                <td colspan="2">
                    <textarea class="w-full forum" name="description" id="description"
                              style="height:160px" rows="5" cols="61" placeholder="Describe your issue here..."
                              required data-bind="textInput: description"><?= old('description') ?></textarea>
                    <p data-bind="visible: descriptionIsNetworkProblem">Please do not use this tool for network issues. See <a href='https://docs.retroachievements.org/FAQ/#how-can-i-get-credit-for-an-achievement-i-earned-but-wasnt-awarded'>here</a> for instructions on how to request a manual unlock.</p>
                    <p data-bind="visible: descriptionIsUnhelpful">Please be more specific with your issue&mdash;such as by adding specific reproduction steps or what you did before encountering it&mdash;instead of simply stating that it doesn't work. The more specific, the better.</p>
                </td>
            </tr>
            <tr>
                <td></td>
                <td colspan="2" class="text-right">
                    <button class="btn" data-bind="disable: descriptionIsUnhelpful">Submit Issue Report</button>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</article>
<script type="text/javascript">
    // TODO replace with alpine
    let ReportViewModel = function () {
        this.description = ko.observable($('#description').val());
        this.emulator = ko.observable($('#emulator').val());
        this.emulator.subscribe(function () {
            displayCore();
        });

        this.descriptionIsNetworkProblem = ko.pureComputed(function () {
            let networkRegex = /(manual\s+unlock|internet)/ig;
            return networkRegex.test(this.description());
        }, this);

        this.descriptionIsUnhelpful = ko.pureComputed(function () {
            let unhelpfulRegex = /(n'?t|not?).*work/ig;
            return this.description().length < 25 && unhelpfulRegex.test(this.description());
        }, this);
    };

    ko.applyBindings(new ReportViewModel());
</script>
<?php RenderContentEnd(); ?>
