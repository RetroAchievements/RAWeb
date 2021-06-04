<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$errorCode = requestInputSanitized('e');
RenderHtmlStart();
RenderHtmlHead("FAQ");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <?php
    $yOffs = 0;
    //RenderTwitchTVStream( $yOffs );
    ?>
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<b>FAQ</b>";
        echo "</div>";

        echo "<div class='largelist'>";

        echo "<h2 class='longheader'>FAQ</h2>";

        echo "<div id='contents'>";

        echo "<ol style='padding-left:22px;'>";
        echo "<li><a href='#faq1'>What is RetroAchievements.org?</a></li>";
        echo "<li><a href='#faq2'>Where can I find ROMs?</a></li>";
        echo "<li><a href='#faq3'>How do I view the overlay?</a></li>";
        echo "<li><a href='#faq4'>How do I access that overlay?</a></li>";
        echo "<li><a href='#faq5'>What is hardcore mode?</a></li>";
        echo "<li><a href='#faq6'>I want to make achievements!</a></li>";
        echo "<li><a href='#faq7'>These emulators are GPL protected! Where is the source code?</a></li>";
        echo "<li><a href='#faq8'>My welcome email has gone missing!</a></li>";
        echo "<li><a href='#faq9'>This achievement didn't trigger!</a></li>";
        echo "<li><a href='#faq10'>I want to tell the world about RetroAchievements!</a></li>";
        echo "<li><a href='#faq11'>How can I assign a controller button to view the achievement overlay?</a></li>";
        echo "<li><a href='#faq99'>I have another question!</a></li>";

        echo "</ol>";

        echo "</div>";

        echo "<h4 class='longheader' id='faq1'>What is RetroAchievements.org?</h4>";
        echo "<p>
		<b>RetroAchievements.org</b> is a community who collaborate and compete to earn custom-made achievements in classic games through emulation.
		Achievements are made by and for the community.
		We provide various custom-built emulators for you to use which will detect when you have completed various challenges.
		Once logged in, the emulators will post the achievements you've completed back to the site, so you can check and compare your progress to your friends.<br><br>
		
		Here's an example of RAGens, playing Streets of Rage 2. You will see two leaderboard attempt counters (bottom-right), one achievement popping and finally the overlay (press ESC):<br>
		
		<img src='https://i.imgur.com/p2y9bDH.gif' width='644' height='480' />
		</p>
		";

        echo "<h4 class='longheader' id='faq2'>Where can I find ROMs?</h4>";
        echo "<p>Not here. Unfortunately it is illegal to host or distribute copyright ROMs. To extract the ROM file from your cartridges, you can use a tool such as the <a href='https://www.retrode.org/'>Retrode</a> or similar, then you can use the ROM file with our emulators. Other websites host ROM files that you could use with our emulators, but we do not condone downloading or playing ROMs for games you do not own.</p>";

        echo "<h4 class='longheader' id='faq3'>How do I use the emulator?</h4>";
        echo "<p>Please download the emulator of your choice from <a href='/download.php'>the download page</a>, log in using your username and password, then load a ROM and play! See the following video for a demonstration:<br><br>";

        //	Using RA
        echo '
		<object type="application/x-shockwave-flash" style="width:600px; height:400px;" data="https://www.youtube-nocookie.com/v/rKY2mZjurJw?hl=en&amp;fs=1"><param name="allowFullScreen" value="true"/><param name="allowscriptaccess" value="always"/><param name="movie" value="https://www.youtube.com/v/rKY2mZjurJw?hl=en&amp;fs=1" /></object><br>
		';

        echo "</p>";

        echo "<h4 class='longheader' id='faq4'>How do I access that overlay?</h4>";
        echo "<p>Normally, this will be set up on 'ESC' keyboard key, or the pause function in the game. In most emulators, a game must be active for it to be functional.</p>";

        echo "<h4 class='longheader' id='faq5'>What is hardcore mode?</h4>";
        echo "<p>Hardcore mode is an additional feature to separate out the good gamers from the great gamers: Hardcore mode disables *all* savestate ability in the emulator: you would not be able to save and reload at any time. You would have to complete the game and get the achievements first time, just like it were on the original console. In reward for this, you will earn both the standard and the hardcore achievement, in effect earning double points! A regular game worth 400 points, is now worth 800 if you complete it fully on hardcore! For example: if you complete the game fully for 400 points, you then have the opportunity to earn another 400 on hardcore.</p>";

        echo "<h4 class='longheader' id='faq6'>I want to make achievements!</h4>";
        echo "<p><strong>Good to hear!</strong> The best place to start is here: <a href='https://retroachievements.github.io/docs/Getting-Started-as-an-Achievement-Developer'>Getting Started as an Achievement Developer</a>. Once you're familiar with the Memory Inspector and the other Achievements related dialogs, you can go to other <a href='https://retroachievements.github.io/docs/#developer-docs'>Developer Docs</a>.</p>";

        echo "<p>Please don't hesitate to ask if you need help, don't struggle! The most important thing is to make sure you're enjoying whatever it is you're doing. If you're not having fun, don't do it. But if you get frustrated and want to persist, just drop a message on the <a href='" . getenv('APP_URL') . "/forum.php?c=7'>forums</a> or in our <a href='https://discord.gg/" . getenv('DISCORD_INVITE_ID') . "'>Discord server</a> and someone will get back to you shortly.</p>";

        echo "<h4 class='longheader' id='faq7'>These emulators are GPL protected! Where is the source code?</h4>";
        echo "<p>That is correct! We fully support the open-source initiative, and welcome any changes to the source that builds either emulators or the RA toolset. Please visit <a href='https://sourceforge.net/projects/retroachievers/'>our page on SourceForge</a> and help yourself to the source code. All emulators build with Visual Studio Community 2013 and <a href='http://www.microsoft.com/en-gb/download/details.aspx?id=5770'>Microsoft DirectX SDK (June 2008)</a>. Why do we use such an old DirectX SDK? Some of the emulators were built in early 2000s, and utilise DirectDraw; this is the latest version of DirectX that will support all emulators.</p>";

        echo "<h4 class='longheader' id='faq8'>My welcome email has gone missing!</h4>";
        echo "<p>Unfortunately sometimes the welcome email has been known to go missing. If this happens, please log in with your username/password you registered with, then visit your settings page. At the top you'll find the option to resend your registration email. If you still have trouble after this point, please drop a message to <a href='/user/RAdmin'>RAdmin</a> and we'll get back to you as soon we can!</p>";

        echo "<h4 class='longheader' id='faq9'>This achievement didn't trigger!</h4>";
        echo "<p>We have a new feature built-in to the emulators now that will allow you to report broken achievements that occur at the wrong time, or not at all. Veteran developer <a href='/user/jackolantern'>Jackolantern</a> explains how to use it in this video:<br><br>";

        //	Reporting broken achievements
        echo '
		<object type="application/x-shockwave-flash" style="width:600px; height:400px;" data="https://www.youtube-nocookie.com/v/TTHbm700Y-Y?hl=en&amp;fs=1"><param name="allowFullScreen" value="true"/><param name="allowscriptaccess" value="always"/><param name="movie" value="https://www.youtube.com/v/TTHbm700Y-Y?hl=en&amp;fs=1" /></object><br>
		';

        echo "<h4 class='longheader' id='faq10'>I want to stream on my twitch channel/make a fan site/buy a T-Shirt with RetroAchievements!</h4>";
        echo "<p>Enjoy! Do whatever you like; here's a <a href='" . getenv('ASSET_URL') . "/Images/RA_LogoLarge.png'>link to a high-resolution logo</a>. Please enjoy responsibly and spread the word about RA as you wish ;)<br><br>";

        echo "<h4 class='longheader' id='faq11'>How can I assign a controller button to view the achievement overlay?</h4>";
        echo "<p>Some of the emulators should already be setup for this. Generally ESC on the keyboard should activate it, but if you want to assign it to a controller, see <a href='" . getenv('APP_URL') . "/viewtopic.php?t=2323&c=12084'>this link</a>.<br><br>";

        echo "<h4 class='longheader' id='faq99'>I have another question!</h4>";
        echo "<p>If you have any further questions, just ask! We'd love to hear from you, whether good or bad: you are very welcome on the <a href='/forum.php'>forums</a>, to message us on <a href='https://www.facebook.com/" . getenv('FACEBOOK_CHANNEL') . "'>Facebook</a> or <a href='https://twitter.com/" . getenv('TWITTER_CHANNEL') . "'>Twitter</a>, however you like, and we'll get back to you ASAP!</p>";

        echo "</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
