<?php

$emulators = getActiveEmulatorReleases();
$consoles = getConsoleList();

authenticateFromCookie($user, $permissions, $userDetails);

$staticData = getStaticData();

RenderContentStart("Download a client");
?>
<div id="mainpage">
    <div id='fullcontainer'>

        <h2>Downloads</h2>

        <p class="embedded mb-5">
            <b>Upgrade:</b> If you were brought to this page because the emulator told you
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
                    <a class="" href="<?= $emulator['link'] ?>">Documentation</a>
                <?php endif ?>
            </div>
            <div class="mb-3 flex flex-col lg:flex-row justify-between items-start">
                <div class="flex-1 mb-3">
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
                <div>
                    <?php if ($emulator['latest_version_url_x64'] ?? false): ?>
                        <p class="embedded mb-3 text-right whitespace-nowrap">
                            <a href="<?= config('app.url') . '/' . $emulator['latest_version_url_x64'] ?>">
                                Download v<?= $emulator['latest_version'] ?> x64<br>
                                <small>Windows</small>
                            </a>
                        </p>
                    <?php endif ?>
                    <?php if ($emulator['latest_version_url'] ?? false): ?>
                        <p class="embedded mb-3 text-right whitespace-nowrap">
                            <a href="<?= config('app.url') . '/' . $emulator['latest_version_url'] ?>">
                                Download v<?= $emulator['latest_version'] ?> x86<br>
                                <small>Windows</small>
                            </a>
                        </p>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>

        <?= view('content.source-code')->render() ?>
        <?= view('content.legal')->render() ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
