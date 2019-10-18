<?php
include_once '../../lib/bootstrap.php';

$username = 'luchaos';
$user = GetUserData($username);

$payload = <<<EOF
Hello

twitch.tv
twitch.tv/retroachievements
www.twitch.tv/rcheevos
https://www.twitch.tv/rcheevos

example.org
https://www.twitch.tv/videos/12826295
[user=$username]
www.example.org

https://www.youtube.com/watch?v=r1BVvs_sxrw

<a href="http://www.retroachievements.org">test</a>

http://retroachievements.org/user/$username

https://www.youtube.com/watch?v=Eldywk__eag

[url=http://retroachievements.org]http://retroachievements.org[/url]

www.example.org
EOF;

RenderDocType();
RenderSharedHeader($user);
?>
<body>
<script type='text/javascript' src='/js/wz_tooltip.js'></script>
<div style="width: 360px; margin: auto">
    <?php RenderTopicCommentPayload($payload) ?>
</div>
</body>

