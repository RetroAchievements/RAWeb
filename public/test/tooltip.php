<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

RenderHtmlStart();
RenderSharedHeader();
?>
<body>
<script src='/vendor/wz_tooltip.js'></script>
<div style="width:560px">
    <h1>Tooltip</h1>
    <div>
        <?php echo GetAchievementAndTooltipDiv(1, "<script>alert('Achievement Name Tést 🏆')</script>", "Test <script>alert('Achievement Description')</script>)", "<script>alert('Achievement Points')</script>)", "<script>alert('Game Name')</script>)", "<script>alert('Badge Name')</script>)") ?>
    </div>
    <div>
        <?php echo GetGameAndTooltipDiv(1, "<script>alert('Game Name Tést 🏆')</script>", "", "<script>alert('Console Name')</script>") ?>
    </div>
</div>
</body>
