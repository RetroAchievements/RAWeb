<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

$username = 'luchaos';
$user = GetUserData($username);

$payload = <<<EOF
[code]inline code[/code]

[code]
multi
line
code
[b]formatting works in code?[/b]
[/code]

[code]starting same line
multi
line
code
[b]formatting works in code?[/b]
[/code]
Text in-between without leading or trailing extra lines
[code]
[b]formatting works in code?[/b]
[/code]
[spoiler]test
test
test
[b]test[/b]
[/spoiler]
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

RenderHtmlStart();
RenderSharedHeader();
?>
<body>
<script type='text/javascript' src='/vendor/wz_tooltip.js'></script>
<div style="width: 360px; margin: auto">
    <?php RenderTopicCommentPayload($payload) ?>
</div>
</body>

