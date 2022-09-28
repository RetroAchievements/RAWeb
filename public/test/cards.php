<?php

use App\Support\Shortcode\Shortcode;

RenderContentStart();
?>
<script src='/vendor/wz_tooltip.js'></script>
<article class="flex flex-col gap-3">
    <h1>Cards</h1>
    <div class="flex justify-between">
        <div>
            <?= Shortcode::render("[user=Scott]") ?>
            <?= Shortcode::render("[user=Jamiras]") ?>
            <?= Shortcode::render("[user=luchaos]") ?>
        </div>
        <div>
            <?= renderUserCard('Scott') ?>
        </div>
    </div>
    <div class="flex justify-between">
        <div>
            <?= Shortcode::render("[ach=1]") ?>
        </div>
        <div>
            <?= renderAchievementCard(1) ?>
        </div>
    </div>
    <div class="flex justify-between">
        <div>
            <?= Shortcode::render("[game=1]") ?>
        </div>
        <div>
            <?= renderGameCard(1) ?>
        </div>
    </div>
    <div class="flex justify-between">
        <div>
            <?= Shortcode::render("[ticket=1]") ?>
        </div>
        <div>
            <?= renderTicketCard(1) ?>
        </div>
    </div>
</article>
<?php RenderContentEnd(); ?>
