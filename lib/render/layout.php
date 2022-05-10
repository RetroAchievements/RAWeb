<?php

use RA\Permissions;

function RenderHtmlStart($isOpenGraphPage = false)
{
    echo "<!doctype html>";
    // echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML+RDFa 1.0//EN' 'http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd'>\n";
    echo "<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en' ";

    if ($isOpenGraphPage) {
        echo "prefix=\"og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#\" ";
    }

    echo ">\n";
}

function RenderHtmlEnd()
{
    echo "</html>";
}

function RenderHtmlHead($pageTitle = null)
{
    echo "<head>";
    RenderSharedHeader();
    RenderTitleTag($pageTitle);
    echo "</head>";
}

function RenderSharedHeader()
{
    echo "<link rel='icon' type='image/png' href='/favicon.png'>\n";
    echo "<link rel='image_src' href='/Images/RA_Logo10.png'>\n";
    echo "<meta http-equiv='content-type' content='text/html; charset=UTF-8'>\n";
    echo "<meta name='robots' content='all'>\n";
    echo "<meta name='description' content='Adding achievements to your favourite retro games since 2012'>\n";
    echo "<meta name='keywords' content='games, retro, computer games, mega drive, genesis, rom, emulator, achievements'>\n";
    echo "<meta name='viewport' content='width=device-width,user-scalable = no'/>\n";

    echo '<meta name="theme-color" content="#2C2E30">';
    echo '<meta name="msapplication-TileColor" content="#2C2E30">';
    echo '<meta name="msapplication-TileImage" content="/favicon.png">';
    echo '<link rel="shortcut icon" type="image/png" href="/favicon.png" sizes="16x16 32x32 64x64">';
    echo '<link rel="apple-touch-icon" sizes="120x120" href="/favicon.png">';

    echo "<link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/themes/sunny/jquery-ui.css'>\n";
    echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js'></script>\n";
    echo "<script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js'></script>\n";
    echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js'></script>";
    echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css' />";
    echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js'></script>";
    echo "<script src='https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js' integrity='sha256-qXBd/EfAdjOA2FGrGAG+b3YBn2tn5A6bhz+LSgYD96k=' crossorigin='anonymous'></script>";

    echo "<script>window.assetUrl='" . getenv('ASSET_URL') . "'</script>\n";
    if (getenv('APP_ENV') === 'local') {
        echo "<script src='/js/all.js?v=" . random_int(0, mt_getrandmax()) . "'></script>\n";
    } else {
        echo "<script src='/js/all-" . VERSION . ".js'></script>\n";
    }
    if (getenv('APP_ENV') === 'local') {
        echo "<link rel='stylesheet' href='/css/styles.css?" . random_int(0, mt_getrandmax()) . "' media='screen'>\n";
    } else {
        echo "<link rel='stylesheet' href='/css/styles-" . VERSION . ".css' media='screen'>\n";
    }
    $customCSS = RA_ReadCookie('RAPrefs_CSS');
    if ($customCSS !== false && mb_strlen($customCSS) > 2) {
        echo "<link rel='stylesheet' href='$customCSS?v=" . VERSION . "' media='screen'>\n";
    }
}

function RenderOpenGraphMetadata($title, $OGType, $imageURL, $thisURL, $description)
{
    echo "<meta property='og:type' content='retroachievements:$OGType'>\n";
    echo "<meta property='og:image' content='" . getenv('ASSET_URL') . "$imageURL'>\n";
    echo "<meta property='og:url' content='" . getenv('APP_URL') . "$thisURL'>\n";
    echo "<meta property='og:title' content=\"$title\">\n";
    echo "<meta property='og:description' content=\"$description\">\n";
}

function RenderTitleTag($title = null)
{
    echo "<title>";
    if ($title !== null) {
        echo "$title - ";
    }
    echo getenv('APP_NAME');
    echo "</title>";
    // <!-- YAY XMAS! -->
    // echo "<script src='js/snowstorm.js'></script>
    // <script>
    //     $( function() {
    //         // Onload:
    //         $('body').append( \"<img src='https://i.retroachievements.org/Images/003754.png' width='280' height='280' style='position:fixed;left:0px;top:0px;width:100%;height:100%;z-index:-50;'>\" );
    //     });
    // </script>";
}

function RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions = 0, $deleteRequested = null)
{
    settype($unreadMessageCount, "integer");
    settype($truePoints, 'integer');

    // js tooltip code is basically on every page:
    echo "<script src='/vendor/wz_tooltip.js'></script>";

    echo "<div id='topborder'></div>\n";

    echo "<div id='title'>";

    echo "<div id='logocontainer'><a id='logo' href='/'><img src='/Images/RA_Logo10.png' alt='Retro Achievements logo'></a>";

    if (!empty($deleteRequested)) {
        echo "<div style='text-align: center; font-size:14px; color:#dd0000'>Your account is marked to be deleted on " .
            getDeleteTime($deleteRequested) . "</div>";
    }

    echo "</div>";

    echo "<div class='login'>";

    if ($user == false) {
        echo "<div style='float:right; font-size:75%;'><a href='/resetPassword.php'>Forgot password?</a></div>";
        echo "<b>login</b> to " . getenv('APP_NAME') . ":<br>";

        echo "<form method='post' action='/request/auth/login.php'>";
        echo "<div>";
        echo "<input type='hidden' name='r' value='" . $_SERVER['REQUEST_URI'] . "'>";
        echo "<table><tbody>";
        echo "<tr>";
        echo "<td>User:&nbsp;</td>";
        echo "<td><input type='text' name='u' size='16' class='loginbox' value=''></td>";
        echo "<td></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Pass:&nbsp;</td>";
        echo "<td><input type='password' name='p' size='16' class='loginbox' value=''></td>";
        echo "<td style='width: 45%'><input type='submit' value='Login' name='submit' class='loginbox'></td>";
        echo "</tr>";
        echo "</tbody></table>";
        echo "</div>";
        echo "</form>";

        if (!isset($errorCode)) {
            echo "<div class='rightalign'>...or <a href='/createaccount.php'>create a new account</a></div>";
        }
    } else {
        echo "<p>";
        echo "<img src='/UserPic/$user.png' alt='Profile Picture' style='float:right' width='64' height='64' class='userpic'>";

        if ($errorCode == "validatedEmail") {
            echo "Welcome, <a href='/user/$user'>$user</a>!<br>";
        } else {
            echo "<strong><a href='/user/$user'>$user</a></strong> ($points) <span class='TrueRatio'>($truePoints)</span><br>";
        }

        echo "<a href='/request/auth/logout.php?Redir=" . $_SERVER['REQUEST_URI'] . "'>logout</a><br>";

        $mailboxIcon = $unreadMessageCount > 0 ? getenv('ASSET_URL') . '/Images/_MailUnread.png' : getenv('ASSET_URL') . '/Images/_Mail.png';
        echo "<a href='/inbox.php'>";
        echo "<img id='mailboxicon' alt='Mailbox Icon' style='float:left' src='$mailboxIcon' width='20' height='20'/>";
        echo "&nbsp;";
        echo "(";
        echo "<span id='mailboxcount'>$unreadMessageCount</span>";
        echo ")";
        echo "</a>";

        if ($permissions >= Permissions::Developer) {
            $openTickets = countOpenTicketsByDev($user);
            if ($openTickets > 0) {
                echo " - <a href='/ticketmanager.php?u=$user'>";
                echo "<font color='red'>Tickets: <strong>$openTickets</strong></font>";
                echo "</a>";
            }
        }

        echo "</p>";
    }

    echo "<br>";
    echo "</div>";

    echo "</div>";
}

function RenderToolbar($user, $permissions = 0)
{
    echo "<div id='innermenu'>";
    echo "<ul id='menuholder'>";

    echo "<li><a href='#'>Games</a>";
    echo "<div>";
    echo "<ul>";
    // Nintendo
    echo "<li class='dropdown-header'>Nintendo</li>";
    echo "<li><a href='/gameList.php?c=4'>Game Boy</a></li>";
    echo "<li><a href='/gameList.php?c=6'>Game Boy Color</a></li>";
    echo "<li><a href='/gameList.php?c=5'>Game Boy Advance</a></li>";
    echo "<li><a href='/gameList.php?c=7'>NES/Famicom</a></li>";
    echo "<li><a href='/gameList.php?c=3'>SNES/Super Famicom</a></li>";
    echo "<li><a href='/gameList.php?c=2'>Nintendo 64</a></li>";
    echo "<li><a href='/gameList.php?c=18'>Nintendo DS</a></li>";
    echo "<li><a href='/gameList.php?c=24'>Pokemon Mini</a></li>";
    echo "<li><a href='/gameList.php?c=28'>Virtual Boy</a></li>";
    // Sony
    echo "<li class='dropdown-header'>Sony</li>";
    echo "<li><a href='/gameList.php?c=12'>PlayStation</a></li>";
    echo "<li><a href='/gameList.php?c=41'>PlayStation Portable</a></li>";
    // Atari
    echo "<li class='dropdown-header'>Atari</li>";
    echo "<li><a href='/gameList.php?c=25'>Atari 2600</a></li>";
    echo "<li><a href='/gameList.php?c=51'>Atari 7800</a></li>";
    echo "<li><a href='/gameList.php?c=17'>Atari Jaguar</a></li>";
    echo "<li><a href='/gameList.php?c=13'>Atari Lynx</a></li>";
    // NEC
    echo "<li class='dropdown-header'>NEC</li>";
    echo "<li><a href='/gameList.php?c=8'>PC Engine/TurboGrafx-16</a></li>";
    echo "<li><a href='/gameList.php?c=47'>PC-8000/8800</a></li>";
    echo "<li><a href='/gameList.php?c=49'>PC-FX</a></li>";
    echo "</ul>";

    echo "<ul>";
    // Sega
    echo "<li class='dropdown-header'>Sega</li>";
    echo "<li><a href='/gameList.php?c=33'>SG-1000</a></li>";
    echo "<li><a href='/gameList.php?c=11'>Master System</a></li>";
    echo "<li><a href='/gameList.php?c=15'>Game Gear</a></li>";
    echo "<li><a href='/gameList.php?c=1'>Genesis/Mega Drive</a></li>";
    echo "<li><a href='/gameList.php?c=9'>Sega CD</a></li>";
    echo "<li><a href='/gameList.php?c=10'>Sega 32X</a></li>";
    echo "<li><a href='/gameList.php?c=39'>Sega Saturn</a></li>";
    echo "<li><a href='/gameList.php?c=40'>Sega Dreamcast</a></li>";
    // Other
    echo "<li class='dropdown-header'>Other</li>";
    echo "<li><a href='/gameList.php?c=43'>3DO Interactive Multiplayer</a></li>";
    echo "<li><a href='/gameList.php?c=37'>Amstrad CPC</a></li>";
    echo "<li><a href='/gameList.php?c=27'>Arcade</a></li>";
    echo "<li><a href='/gameList.php?c=38'>Apple II</a></li>";
    echo "<li><a href='/gameList.php?c=44'>ColecoVision</a></li>";
    echo "<li><a href='/gameList.php?c=45'>Intellivision</a></li>";
    echo "<li><a href='/gameList.php?c=23'>Magnavox Odyssey 2</a></li>";
    echo "<li><a href='/gameList.php?c=29'>MSX</a></li>";
    echo "<li><a href='/gameList.php?c=14'>Neo Geo Pocket</a></li>";
    echo "<li><a href='/gameList.php?c=46'>Vectrex</a></li>";
    echo "<li><a href='/gameList.php?c=63'>Watara Supervision</a></li>";
    echo "<li><a href='/gameList.php?c=53'>WonderSwan</a></li>";
    echo "</ul>";

    echo "<ul>";
    echo "<li><a href='/gameList.php'>All Games</a></li>";
    // echo "<li><a href='/popularGames.php'>Most Played</a></li>";
    echo "<li><a href='/setRequestList.php'>Most Requested</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</li>";

    echo "<li><a href='#'>Achievements</a>";
    echo "<div>";
    echo "<ul>";
    echo "<li><a href='/achievementList.php'>All Achievements</a></li>";
    echo "<li class='divider'></li>";
    // echo "<li><a href='/awardedList.php'>Commonly Won Achievements</a></li>";
    echo "<li><a href='/achievementList.php?s=4&p=2'>Easy Achievements</a></li>";
    echo "<li><a href='/gameSearch.php?p=0'>Hardest Achievements</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</li>";

    echo "<li><a href='#'>Community</a>";
    echo "<div>";
    echo "<ul>";
    echo "<li><a href='/forum.php'>Forums</a></li>";
    echo "<li><a href='/forum.php?c=1'>- Community</a></li>";
    echo "<li><a href='/viewforum.php?f=25'>+- Competitions</a></li>";
    echo "<li><a href='/forum.php?c=7'>- Developers</a></li>";
    echo "<li><a href='/forumposthistory.php'>Recent Posts</a></li>";
    echo "<li class='divider'></li>";
    echo "<li><a href='/userList.php'>Users</a></li>";
    echo "<li><a href='/developerstats.php'>Developers</a></li>";
    // echo "<li><a href='/leaderboardList.php'>Leaderboards</a></li>";
    echo "<li><a href='/globalRanking.php'>Global Ranking</a></li>";
    echo "<li><a href='/recentMastery.php'>Recent Masteries</a></li>";
    echo "<li class='divider'></li>";
    echo "<li><a href='https://docs.retroachievements.org/'>User Documentation</a></li>";
    echo "<li><a href='https://docs.retroachievements.org/Developer-docs/'>Developer Documentation</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</li>";

    echo "<li><a href='/download.php'>Download</a></li>";

    if (isset($user) && $user != "") {
        echo "<li><a href='#'>My Pages</a>";
        echo "<div>";
        echo "<ul>";
        echo "<li><a href='/user/$user'>Profile</a></li>";
        echo "<li><a href='/gameList.php?d=$user'>My Sets</a></li>";
        echo "<li><a href='/ticketmanager.php?u=$user'>My Tickets</a></li>";
        echo "<li><a href='/achievementList.php?s=14&p=1'>Achievements</a></li>";
        echo "<li><a href='/friends.php'>Friends</a></li>";
        echo "<li><a href='/history.php'>History</a></li>";
        echo "<li><a href='/inbox.php'>Messages</a></li>";
        echo "<li><a href='/setRequestList.php?u=$user'>Requested Sets</a></li>";
        echo "<li class='divider'></li>";
        echo "<li><a href='/controlpanel.php'>Settings</a></li>";
        echo "<li class='divider'></li>";
        echo "<li><a href='/request/auth/logout.php'>Log Out</a></li>";
        echo "</ul>";
        echo "</div>";
        echo "</li>";
    } else {
        echo "<li><a href='/createaccount.php'>Create Account</a></li>";
    }

    if ($permissions >= Permissions::JuniorDeveloper) {
        echo "<li><a href='#'>Manage</a>";
        echo "<div>";
        echo "<ul>";
        // SU
        if ($permissions >= Permissions::Developer) {
            echo "<li><a href='/submitnews.php'>News Articles</a></li>";
            echo "<li class='divider'></li>";
        }
        echo "<li><a href='/ticketmanager.php'>Ticket Manager</a></li>";
        echo "<li><a href='/ticketmanager.php?f=1'>Most Reported Games</a></li>";
        echo "<li><a href='/achievementinspector.php'>Achievement Inspector</a></li>";
        echo "<li><a href='/setRequestList.php'>Most Requested Sets</a></li>";
        echo "<li class='divider'></li>";
        echo "<li><a href='/latesthasheslinked.php'>Latest Linked Hashes</a></li>";
        // Admin
        if ($permissions >= Permissions::Admin) {
            echo "<li class='divider'></li>";
            echo "<li><a href='/viewforum.php?f=0'>Invalid Forum Posts</a></li>";
            echo "<li><a href='/admin.php'>Admin Tools</a></li>";
        }
        echo "</ul>";
        echo "</div>";
        echo "</li>";
    }

    $searchQuery = null;
    if ($_SERVER['SCRIPT_NAME'] === '/searchresults.php') {
        $searchQuery = attributeEscape(requestInputQuery('s', null));
    }
    echo "<form action='/searchresults.php' method='get'>";
    echo "<div class='searchbox'>";
    // echo "Search:&nbsp;";
    echo "<input size='24' name='s' type='text' class='searchboxinput' value='$searchQuery' placeholder='Search the site...'>";
    echo "&nbsp;";
    echo "<input type='submit' value='Search'>";
    echo "</div>";
    echo "</form>";

    echo "<div class='searchbox'>";
    RenderThemeSelector();
    echo "</div>";

    echo '<div style="clear:both;"></div>';

    echo "</ul>";
    echo "</div>";
}

function RenderHeader($userDetails)
{
    $errorCode = requestInputSanitized('e');

    if ($userDetails) {
        RenderTitleBar($userDetails['User'], $userDetails['RAPoints'],
            $userDetails['TrueRAPoints'], $userDetails['UnreadMessageCount'],
            $errorCode, $userDetails['Permissions'],
            $userDetails['DeleteRequested']);
        RenderToolbar($userDetails['User'], $userDetails['Permissions']);
    } else {
        RenderTitleBar(null, 0, 0, 0, $errorCode);
        RenderToolbar(null, 0);
    }
}

function RenderFooter()
{
    echo "<div style='clear:both;'></div>";

    echo "<footer id='footer'>";

    echo "<div>";
    echo "<h4>RetroAchievements</h4>";
    echo "<div><a href='/achievementList.php'>Achievements</a></div>";
    echo "<div><a href='/leaderboardList.php'>Leaderboards</a></div>";
    echo "<div><a href='/gameList.php'>Games</a></div>";
    echo "</div>";

    echo "<div>";
    echo "<h4>Documentation</h4>";
    echo "<div><a href='https://docs.retroachievements.org/Developers-Code-of-Conduct/' target='_blank'>Developers Code of Conduct</a></div>";
    echo "<div><a href='https://docs.retroachievements.org/Users-Code-of-Conduct/' target='_blank'>Users Code of Conduct</a></div>";
    echo "<div><a href='https://docs.retroachievements.org/FAQ/' target='_blank'>FAQ</a></div>";
    echo "<div><a href='/APIDemo.php'>API</a></div>";
    echo "</div>";

    echo "<div>";
    echo "<h4>Community</h4>";
    echo "<div><a href='/forum.php'>Forums</a></div>";
    echo "<div><a href='/userList.php'>Users</a></div>";
    echo "<div><a href='/developerstats.php'>Developers</a></div>";
    echo "</div>";

    echo "<div>";
    echo "<h4>Connect</h4>";
    if (getenv('PATREON_USER_ID')) {
        echo "<div><a href='https://www.patreon.com/bePatron?u=" . getenv('PATREON_USER_ID') . "' target='_blank'>Patreon</a></div>";
    }
    if (getenv('DISCORD_INVITE_ID')) {
        echo "<div><a href='https://discord.gg/" . getenv('DISCORD_INVITE_ID') . "' target='_blank'>Discord</a></div>";
    }
    if (getenv('GITHUB_ORG')) {
        echo "<div><a href='https://github.com/" . getenv('GITHUB_ORG') . "' target='_blank'>GitHub</a></div>";
    }
    if (getenv('TWITCH_CHANNEL')) {
        echo "<div><a href='https://twitch.tv/" . getenv('TWITCH_CHANNEL') . "' target='_blank'>Twitch</a></div>";
    }
    if (getenv('FACEBOOK_CHANNEL')) {
        echo "<div><a href='https://www.facebook.com/" . getenv('FACEBOOK_CHANNEL') . "/' target='_blank'>Facebook</a></div>";
    }
    if (getenv('TWITTER_CHANNEL')) {
        echo "<div><a href='https://twitter.com/" . getenv('TWITTER_CHANNEL') . "' target='_blank'>Twitter</a></div>";
    }
    echo "<div><a href='/rss.php'>RSS</a></div>";
    echo "</div>";

    // echo "<div>Content by <a href='http://www.immensegames.com' target='_blank'>Immense Games</a></div>";

    // global $g_numQueries;
    // global $g_pageLoadAt;
    // $loadDuration = microtime(true) - $g_pageLoadAt;
    // echo "<p>";
    // echo "Generated from $g_numQueries queries in " . sprintf('%1.3f', ($loadDuration)) . " seconds";
    // if ($loadDuration > 2.4) {
    //     error_log(CurrentPageURL() . " - took " . sprintf('%1.3f', $loadDuration) . " to fetch!");
    // }
    // echo "</p>";

    echo "</footer>";
}

function RenderThemeSelector()
{
    $dirContent = scandir('./css/');

    $cssFileList = [];
    foreach ($dirContent as $filename) {
        $fileStart = mb_strpos($filename, "rac_");
        if ($fileStart !== false) {
            $filename = mb_substr($filename, $fileStart + 4);
            $filename = mb_substr($filename, 0, mb_strlen($filename) - 4);
            $cssFileList[] = $filename;
        }
    }

    $currentCustomCSS = RA_ReadCookie('RAPrefs_CSS');
    $currentCustomCSS = $currentCustomCSS ?: '/css/rac_blank.css';

    echo "<select id='themeselect' onchange='ResetTheme(); return false;'>";
    foreach ($cssFileList as $nextCSS) {
        $cssFull = "/css/rac_" . $nextCSS . ".css";
        $selected = (strcmp($currentCustomCSS, $cssFull) == 0) ? 'selected' : '';
        echo "<option $selected value='$cssFull'>$nextCSS</option>";
    }
    echo "</select>";
}
