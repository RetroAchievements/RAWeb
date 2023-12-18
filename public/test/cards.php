<?php

use App\Platform\Enums\AchievementType;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Facades\Blade;

RenderContentStart();
?>
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
                        'DateAwarded' => date("Y-m-d"),
                        'HardcoreAchieved' => date("Y-m-d"),
                        'Type' => AchievementType::Progression,
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
    <div class="grid">
        <div>
            <div class="mb-3">
                <?= Shortcode::render('[game=1]') ?>
                <?= Shortcode::render('[game=17953]') ?>
            </div>
            <div class="mb-3">
                <?= gameAvatar(1) ?>
            </div>
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <?= Blade::render('<x-game-card gameId="1667" />') ?>
            <?= Blade::render('<x-game-card gameId="17078" />') ?>
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <?= Blade::render('<x-game-card gameId="2791" />') ?>
            <?= Blade::render('<x-game-card gameId="1471" />') ?>
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <?= Blade::render('<x-game-card gameId="17953" />') ?>
            <?= Blade::render('<x-game-card gameId="1" />') ?>
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <?= Blade::render('<x-game-card gameId="12798" />') ?>
            <?= Blade::render('<x-game-card gameId="12192" />') ?>
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <?= Blade::render('<x-game-card gameId="586" />') ?>
            <?= Blade::render('<x-game-card gameId="22561" />') ?>
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <?= Blade::render('<x-game-card gameId="20491" />') ?>
            <?= Blade::render('<x-game-card gameId="16498" />') ?>
        </div>
    </div>

    <h3>Hub</h3>
    <div class="flex justify-between">
        <div>
            <div class="mb-3">
                <?= gameAvatar(8935) ?>
            </div>
            <div>
                <?= Blade::render('<x-game-card gameId="8935" />') ?>
            </div>
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
