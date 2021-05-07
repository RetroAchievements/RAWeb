<?php
require_once __DIR__ . '/../vendor/autoload.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);
RenderHtmlStart();
?>
<head>
    <?php RenderSharedHeader(); ?>
</head>
<body>
<script type='text/javascript' src='/vendor/wz_tooltip.js'></script>
<div style='padding:0 10px;'>
    <?php RenderChat($user, 420); ?>
</div>
</body>
<?php RenderHtmlEnd(); ?>
