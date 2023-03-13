<?php

use App\Support\Shortcode\Shortcode;

$username = 'Scott';
$user = GetUserData($username);

$payload = <<<EOF

Unchanged case: [SLUS-00752] [BAR]

[b][i][u][s]Formatting[/s][/u][/i][/b]
[b][i][u][s]Scrambled Formatting[/i][/b][/u][/s]

[B]Mismatching tags[/b]

[b][i]Code Blocks[/i][/b]

[Code]inline code with mismatching tag case[/CODE]

[code]
multi
line
https://www.youtube.com/watch?v=r1BVvs_sxrw
code
[b]formatting works in code?[/b]
[code]Code within code[/code]
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

[img=https://media.retroachievements.org/Images/043749.png]

https://www.youtube.com/watch?v=r1BVvs_sxrw
https://youtu.be/dMH0bHeiRNg
[/spoiler]

[b][i]Links[/i][/b]

[ach=]
[ach=1] ach=1
[ach="2"] ach="2" (quoted)
[ACH=3] ACH=3 (case corrected)
[game=]
[game=3]
[game="4"]
[ticket=]
[ticket=5]
[ticket="6"]
[user=]
[user=_nope]
[user={$username}]
[user]{$username}[/user]

example.org (no link)
www.example.org (no link)

Trailing sentence-punctuation characters are NOT part of URL:
https://example.com.
https://example.com?
https://example.com?test
https://example.com/?test=test,test.test%20test+test(test)&test-test!
https://example.com/?test=test,test.test%20test+test(test)&test-test,
https://example.com/?test=test,test.test%20test+test(test)&test-test.
https://example.com/?test=test,test.test%20test+test(test)&test-test. continue
https://example.com/?test=test,test.test%20test+test(test)&test-test?
https://example.com/?test=test,test.test%20test+test(test)&test-test;
https://example.com/?test=test,test.test%20test+test(test)&test-test:
https://example.com/?test=test,test.test%20test+test(test)&test-test"
https://example.com/?test=test,test.test%20test+test(test)&test-test'
https://example.com/?test=test,test.test%20test+test(test)&test-test)
https://example.com/?test=test,test.test%20test+test(test)&test-test(
https://example.com/?test=https://example.com/?test=test,test.test%20test+test(test)&test-test
[url="https://example.com/?test=test,test.test%20test+test(test)&test-test"]
[url=https://example.com/?test=test,test.test%20test+test(test)&test-test]https://example.com/?test=test,test.test%20test+test(test)&test-test[/url]

Trailing hyphens ARE part of URL (matches most markdown parsers):
https://example.com/?test=test,test.test%20test+test(test)&test-test-

[url=]
[url="retroachievements.org#1"] | [url="retroachievements.org#2"]
[url="https://retroachievements.org#1"] | [url="https://retroachievements.org#2"]
[url=http://retroachievements.org#1] | [url=http://retroachievements.org#2]
[url]https://retroachievements.org#1[/url] | [url]https://retroachievements.org#2[/url]
[url=https://retroachievements.org#1]URL Shortcode #1[/url] | [url=https://retroachievements.org#2]URL Shortcode #2[/url]

http://mother3.fobby.net/blog/
[url=http://mother3.fobby.net/blog/]

[link url="https://retroachievements.org#1"]Link Shortcode #1[/link] | [link url="http://retroachievements.org#1"]Link Shortcode #2 HTTP -> HTTPS[/link]

<a href="https://www.retroachievements.org">HTML</a>
https://retroachievements.org/user/{$username}


[b][i]Images[/i][/b]

[img=]
https://media.retroachievements.org/Images/043749.png
[img=https://media.retroachievements.org/Images/043749.png]

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

RenderContentStart();
?>
<div style="width:560px;margin:10px;">
    <h1>Shortcode</h1>
    <?php echo Shortcode::render($payload, ['imgur' => true]) ?>
</div>
<?php RenderContentEnd(); ?>
