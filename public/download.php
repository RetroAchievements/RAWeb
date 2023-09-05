<?php

use Illuminate\Support\Facades\Blade;

$emulators = getActiveEmulatorReleases();
usort($emulators, function ($a, $b) {
    return strcasecmp($a['handle'], $b['handle']);
});

authenticateFromCookie($user, $permissions, $userDetails);

RenderContentStart("Download a client");
?>
<article>
    <h2 class="mb-6">Emulators supporting RetroAchievements</h2>

    <?php foreach ($emulators as $emulator): ?>
        <h2 class="longheader" id="<?= mb_strtolower($emulator['handle'] ?? null) ?>">
            <a href="#<?= mb_strtolower($emulator['handle'] ?? null) ?>"><?= $emulator['handle'] ?? null ?></a>
            <?php if ($emulator['handle'] != $emulator['name']): ?>
                <small>(<?= $emulator['name'] ?? null ?>)</small>
            <?php endif ?>
        </h2>
        <div class="flex flex-col lg:flex-row justify-between items-start mb-6">
            <div class="mb-3 w-full">
                <?php if ($emulator['description'] ?? false): ?>
                    <div class="mb-2"><?= nl2br($emulator['description']) ?></div>
                <?php endif ?>
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
            </div>
            <div>
                <?php if ($emulator['download_url'] ?? false): ?>
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <?= Blade::render("<x-link link=\"" . $emulator['download_url'] . "\" >Download</x-link>") ?>
                    </p>
                <?php endif ?>
                <?php if ($emulator['latest_version_url_x64'] ?? false): ?>
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <a href="<?= config('app.url') . '/' . $emulator['latest_version_url_x64'] ?>">
                            Download v<?= $emulator['latest_version'] ?> x64<br>
                            <small>Windows</small>
                        </a>
                    </p>
                <?php endif ?>
                <?php if ($emulator['latest_version_url'] ?? false): ?>
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <a href="<?= config('app.url') . '/' . $emulator['latest_version_url'] ?>">
                            Download v<?= $emulator['latest_version'] ?> x86<br>
                            <small>Windows</small>
                        </a>
                    </p>
                <?php endif ?>
                <?php if ($emulator['link'] ?? false): ?>
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <?= Blade::render("<x-link link=\"" . $emulator['link'] . "\" >Documentation</x-link>") ?>
                    </p>
                <?php endif ?>
                <?php if ($emulator['source'] ?? false): ?>
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <?= Blade::render("<x-link link=\"" . $emulator['source'] . "\" >Source Code</x-link>") ?>
                    </p>
                <?php endif ?>
            </div>
        </div>
    <?php endforeach ?>

    <?= view('content.legal')->render() ?>
</article>
<?php RenderContentEnd(); ?>
