<?php

use App\Support\Shortcode\Shortcode;

RenderContentStart();
?>
<script src='/vendor/wz_tooltip.js'></script>
<article class="flex flex-col gap-3">
    <h1>Cards</h1>

    <h3>User</h3>
    <div class="flex justify-between">
        <div class="mb-3">
            <div>
                <?= Shortcode::render("[user=Scott]") ?>
            </div>
            <div>
                <?= Shortcode::render("[user=Jamiras]") ?>
            </div>
            <div>
                <?= Shortcode::render("[user=luchaos]") ?>
            </div>
        </div>
        <div class="mb-3">
            <div>Label & Icon</div>
            <div>
                <?= userAvatar('Scott') ?>
            </div>
            <div>
                <?= userAvatar('Jamiras') ?>
            </div>
            <div>
                <?= userAvatar('luchaos') ?>
            </div>
        </div>
        <div class="mb-3">
            <div>Label & Icon</div>
            <?= userAvatar('Scott', label: true, icon: true) ?>
        </div>
        <div class="mb-3">
            <div>Label</div>
            <?= userAvatar('Scott', label: true, icon: false) ?>
        </div>
        <div class="mb-3">
            <div>Icon</div>
            <?= userAvatar('Scott', label: false, icon: true) ?>
        </div>
        <div class="mb-3">
            <div>Label</div>
            <?= userAvatar('Scott', label: true) ?>
        </div>
        <div class="mb-3">
            <div>Icon</div>
            <?= userAvatar('Scott', label: false) ?>
        </div>
        <div class="mb-3">
            <div>Icon</div>
            <?= userAvatar('Scott', icon: true) ?>
        </div>
        <div class="mb-3">
            <div>Label</div>
            <?= userAvatar('Scott', icon: false) ?>
        </div>
        <div>
            <?= renderUserCard('Scott') ?>
        </div>
    </div>

    <h3>Achievement</h3>
    <div class="flex justify-between">
        <div>
            <div class="mb-3">
                <?= Shortcode::render("[ach=1]") ?>
            </div>
            <div class="mb-3">
                <?= achievementAvatar(1, context: 'Scott') ?>
            </div>
            <div class="mb-3">
                <?= achievementAvatar(
                    [
                        'ID' => 1,
                        'Title' => 'Achievement 1 with pre-rendered tooltip',
                    ],
                ) ?>
            </div>
            <div class="mb-3">
                <?= achievementAvatar(
                    [
                        'ID' => 2,
                        'Title' => 'Achievement 2 with pre-fetched tooltip data',
                        'Description' => 'Description Preloaded Data',
                        'Points' => 'Points Preloaded Data',
                        'BadgeName' => '000000',
                        'Unlock' => 'Unlock Preloaded Data',
                    ]
                )
                ?>
            </div>
        </div>
        <div>
            <?= renderAchievementCard(1, context: 'Unlocked') ?>
            <?= renderAchievementCard([
                'ID' => 'ID Preloaded Data',
                'Title' => 'Title Preloaded Data',
                'Description' => 'Description Preloaded Data',
                'Points' => 'Points Preloaded Data',
                'BadgeName' => '000000',
                'Unlock' => 'Unlock Preloaded Data',
            ]) ?>
        </div>
    </div>

    <h3>Game</h3>
    <div class="flex justify-between">
        <div>
            <div class="mb-3">
                <?= Shortcode::render('[game=1]') ?>
                <?= Shortcode::render('[game=17953]') ?>
            </div>
            <div class="mb-3">
                <?= gameAvatar(1) ?>
            </div>
        </div>
        <div>
            <?= renderGameCard(1) ?>
            <?= renderGameCard(17078) ?>
            <?= renderGameCard(17953) ?>
        </div>
    </div>

    <h3>Ticket</h3>
    <div class="flex justify-between">
        <div>
            <div class="mb-3">
                <?= Shortcode::render("[ticket=1]") ?>
            </div>
            <div class="mb-3">
                <?= ticketAvatar(1) ?>
            </div>
        </div>
        <div>
            <?= renderTicketCard(1) ?>
        </div>
    </div>
</article>
<?php RenderContentEnd(); ?>
