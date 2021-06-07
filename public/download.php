<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$emulators = getActiveEmulatorReleases();
$consoles = getConsoleList();

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$errorCode = requestInputSanitized('e');
$staticData = getStaticData();

RenderHtmlStart();
RenderHtmlHead("Download a client");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id='fullcontainer'>

        <h2 class='longheader'>Note About Upgrading</h2>
        <p class="mb-3">
            If you were brought to this page because the emulator told you
            that a new version is available, download the new version and
            extract it over the existing folder. This way you won't lose
            any save files that you may have.
        </p>

        <?php foreach ($emulators as $emulator): ?>
            <h2 class="longheader" id="<?= mb_strtolower($emulator['handle'] ?? null) ?>">
                <a href="#<?= mb_strtolower($emulator['handle'] ?? null) ?>"><?= $emulator['handle'] ?? null ?></a> <small>(<?= $emulator['name'] ?? null ?>)</small>
            </h2>
            <div class="mb-1">
                <?php if ($emulator['description'] ?? false): ?>
                    <?= nl2br($emulator['description']) ?><br>
                <?php endif ?>
                <?php if ($emulator['link'] ?? false): ?>
                    <a class="" href="<?= $emulator['link'] ?>" target="_blank">Documentation</a>
                <?php endif ?>
            </div>
            <div class="mb-3 download-flex">
                <div style="flex-grow: 1">
                    <?php if (!empty($emulator['systems'])): ?>
                        <?php sort($emulator['systems']) ?>
                        <b>Supported Systems:</b><br>
                        <ul style="column-count: 3">
                        <?php foreach ($emulator['systems'] as $system): ?>
                            <?php
                            sanitize_outputs($system);
                            ?>
                            <li>- <?= $system ?></li>
                        <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
                <?php if ($emulator['latest_version_url_x64'] ?? false): ?>
                    <p class="mb-1 text-right text-nowrap" style="margin-right: 0.5rem">
                        <a href="<?= getenv('APP_URL') . '/' . $emulator['latest_version_url_x64'] ?>">
                            Download v<?= $emulator['latest_version'] ?> x64<br>
                            <small>Windows</small>
                        </a>
                    </p>
                <?php endif ?>
                <?php if ($emulator['latest_version_url'] ?? false): ?>
                    <p class="mb-1 text-right text-nowrap">
                        <a href="<?= getenv('APP_URL') . '/' . $emulator['latest_version_url'] ?>">
                            Download v<?= $emulator['latest_version'] ?> x86<br>
                            <small>Windows</small>
                        </a>
                    </p>
                <?php endif ?>
            </div>
        <?php endforeach ?>

        <h3>Source Code</h3>
        <p class="mb-3">All RetroAchievements emulators are released as free software. Licensing terms for usage and distribution of derivative works may vary depending on the emulator. Please consult the license information of each one for more details.
            <br>
            Standalone Emulators repository: <a href='https://github.com/RetroAchievements/RAEmus'>https://github.com/RetroAchievements/RAEmus</a>
            <br>
            RALibretro repository: <a href='https://github.com/RetroAchievements/RALibretro'>https://github.com/RetroAchievements/RALibretro</a>
        </p>

        <h3>Legal</h3>
        <p class="mb-3">
            RetroAchievements.org does not condone or supply any copyright-protected ROMs to be used in conjunction with the emulator supplied within.
            There are no copyright-protected ROMs available for download on RetroAchievements.org.
        </p>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
