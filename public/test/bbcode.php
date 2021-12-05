<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

$username = 'Scott';
$user = GetUserData($username);

$payload = <<<EOF

[b]BBCode / Shortcode Tests[/b]

[b][i]Code Blocks[/i][/b]

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

[b][i]Spoiler[/i][/b]

[spoiler]test
test
test
[b]test[/b]
[code]Code within spoiler[/code]

[b]Embeds in spoiler[/b]
[img=https://retroachievements.org/Images/043749.png] 
https://www.youtube.com/watch?v=r1BVvs_sxrw
https://youtu.be/dMH0bHeiRNg

[/spoiler]

[b][i]Links[/i][/b]

[user=$username]
example.org (no link)
www.example.org (no link)
[url=https://retroachievements.org]URL Shortcode[/url]
<a href="http://www.retroachievements.org">HTML</a>
https://retroachievements.org/user/$username

[ach=1]
[game=1]
[ticket=1]

[b][i]Images[/i][/b]

https://retroachievements.org/Images/043749.png
[img=https://retroachievements.org/Images/043749.png] 

[b][i]YouTube[/i][/b]

[url=https://www.youtube.com/watch?v=r1BVvs_sxrw]Shortcode[/url]
[url=https://www.youtube.com/watch?v=r1BVvs_sxrw  ] Shortcode https://www.youtube.com/watch?v=r1BVvs_sxrw [/url]
<a href="https://www.youtube.com/watch?v=r1BVvs_sxrw">Link</a>
<a href=" https://www.youtube.com/watch?v=r1BVvs_sxrw  "> Link https://www.youtube.com/watch?v=r1BVvs_sxrw </a>

https://www.youtube.com/watch?v=r1BVvs_sxrw

[b][i]youtu.be[/i][/b]

[url=https://www.youtu.be/dMH0bHeiRNg]Shortcode[/url]
[url=https://www.youtu.be/dMH0bHeiRNg  ] Shortcode https://www.youtu.be/dMH0bHeiRNg [/url]
<a href="https://www.youtu.be/dMH0bHeiRNg">Link</a>
<a href=" https://www.youtu.be/dMH0bHeiRNg "> Link https://www.youtu.be/dMH0bHeiRNg </a>

https://www.youtu.be/dMH0bHeiRNg

[b][i]Twitch[/i][/b]

[url=https://www.twitch.tv/videos/12826295]Shortcode[/url]
[url=https://www.youtu.be/dMH0bHeiRNg  ] Shortcode https://www.youtu.be/dMH0bHeiRNg [/url]
<a href="https://www.twitch.tv/videos/12826295">Link</a>
<a href=" https://www.twitch.tv/videos/12826295 "> Link https://www.twitch.tv/videos/12826295 </a>
https://www.twitch.tv/rcheevos

https://www.twitch.tv/videos/12826295

[b][i]Imgur[/i][/b]

[url=https://www.twitch.tv/videos/12826295]Shortcode[/url]
[url=https://www.youtu.be/dMH0bHeiRNg  ] Shortcode https://www.youtu.be/dMH0bHeiRNg [/url]
<a href="https://i.imgur.com/MaYu3L8.mp4">Link</a>
<a href=" https://i.imgur.com/MaYu3L8.mp4 "> Link https://imgur.com/a/MaYu3L8.jpg </a>

[img=https://imgur.com/a/MaYu3L8.png]  (CORS error)

https://imgur.com/gallery/MaYu3L8 (no extension -&gt; link)

https://imgur.com/gallery/MaYu3L8.gifv
https://imgur.com/a/MaYu3L8.gifv
https://i.imgur.com/MaYu3L8.gifv
https://i.imgur.com/MaYu3L8.webm
https://i.imgur.com/MaYu3L8.mp4
https://imgur.com/a/MaYu3L8.gif
https://imgur.com/a/MaYu3L8.jpg
https://imgur.com/a/MaYu3L8.png
https://imgur.com/a/MaYu3L8.jpeg

EOF;

RenderHtmlStart();
RenderSharedHeader();
?>
<body>
<script src='/vendor/wz_tooltip.js'></script>
<div style="width: 360px; margin: auto">
    <?php echo parseTopicCommentPHPBB(nl2br($payload), true) ?>
</div>
</body>

