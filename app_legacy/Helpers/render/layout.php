<?php

use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;

// see resources/views/layouts/app.blade.php
// see resources/views/layouts/partials/head.blade.php

function RenderContentStart($pageTitle = null): void
{
    // hijack view variables
    view()->share('pageTitle', $pageTitle);

    // TBD add legacy content wrapper start
}

function RenderContentEnd(): void
{
    // TBD add legacy content wrapper end
}

function RenderOpenGraphMetadata($title, $OGType, $imageURL, $description): void
{
    // hijack view variables
    view()->share('pageTitle', $title);
    view()->share('pageDescription', $description);
    if ($OGType) {
        view()->share('pageType', 'retroachievements:' . $OGType);
    }
    view()->share('pageImage', $imageURL);
}

function RenderToolbar(): void
{
    /** @var ?User $user */
    $user = request()->user();

    $username = $user?->getAttribute('User');
    $permissions = $user?->getAttribute('Permissions') ?? 0;

    echo "<ul class='flex-1'>";

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
    // echo "<li><a href='/gameList.php?c=78'>Nintendo DSi</a></li>";
    echo "<li><a href='/gameList.php?c=24'>Pokemon Mini</a></li>";
    echo "<li><a href='/gameList.php?c=28'>Virtual Boy</a></li>";
    // Sony
    echo "<li class='dropdown-header'>Sony</li>";
    echo "<li><a href='/gameList.php?c=12'>PlayStation</a></li>";
    echo "<li><a href='/gameList.php?c=21'>PlayStation 2</a></li>";
    echo "<li><a href='/gameList.php?c=41'>PlayStation Portable</a></li>";
    // Atari
    echo "<li class='dropdown-header'>Atari</li>";
    echo "<li><a href='/gameList.php?c=25'>Atari 2600</a></li>";
    echo "<li><a href='/gameList.php?c=51'>Atari 7800</a></li>";
    echo "<li><a href='/gameList.php?c=17'>Atari Jaguar</a></li>";
    // echo "<li><a href='/gameList.php?c=77'>Atari Jaguar CD</a></li>";
    echo "<li><a href='/gameList.php?c=13'>Atari Lynx</a></li>";
    // NEC
    echo "<li class='dropdown-header'>NEC</li>";
    echo "<li><a href='/gameList.php?c=8'>PC Engine/TurboGrafx-16</a></li>";
    echo "<li><a href='/gameList.php?c=76'>PC Engine CD/TurboGrafx-CD</a></li>";
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
    echo "<li><a href='/gameList.php?c=38'>Apple II</a></li>";
    echo "<li><a href='/gameList.php?c=27'>Arcade</a></li>";
    echo "<li><a href='/gameList.php?c=71'>Arduboy</a></li>";
    echo "<li><a href='/gameList.php?c=44'>ColecoVision</a></li>";
    echo "<li><a href='/gameList.php?c=57'>Fairchild Channel F</a><li>";
    echo "<li><a href='/gameList.php?c=45'>Intellivision</a></li>";
    echo "<li><a href='/gameList.php?c=23'>Magnavox Odyssey 2</a></li>";
    echo "<li><a href='/gameList.php?c=69'>Mega Duck</a></li>";
    echo "<li><a href='/gameList.php?c=29'>MSX</a></li>";
    echo "<li><a href='/gameList.php?c=14'>Neo Geo Pocket</a></li>";
    echo "<li><a href='/gameList.php?c=46'>Vectrex</a></li>";
    echo "<li><a href='/gameList.php?c=72'>WASM-4</a></li>";
    echo "<li><a href='/gameList.php?c=63'>Watara Supervision</a></li>";
    echo "<li><a href='/gameList.php?c=53'>WonderSwan</a></li>";
    echo "</ul>";

    echo "<ul>";
    echo "<li class='dropdown-header'>Miscellaneous</li>";
    echo "<li><a href='/gameList.php'>All Games</a></li>";
    // echo "<li><a href='/popularGames.php'>Most Played</a></li>";
    echo "<li><a href='/setRequestList.php'>Most Requested</a></li>";
    echo "<li><a href='/claimlist.php?s=9&f=8109'>New Sets & Revisions</a></li>";
    echo "<li><a href='/claimlist.php'>Sets in Progress</a></li>";
    echo "<li class='dropdown-header'>Hubs</li>";
    echo "<li><a href='/gameList.php?s=6&c=100&f=1'>Hub List</a></li>";
    echo "<li><a href='/game/6914'>Central Hub</a></li>";
    echo "<li><a href='/game/9553'>Genre & Subgenre Hub</a></li>";
    echo "<li><a href='/game/5771'>Series Hub</a></li>";
    echo "<li><a href='/game/3105'>Community Events Hub</a></li>";
    echo "<li><a href='/game/3273'>Developer Events Hub</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</li>";

    echo "<li><a href='#'>Achievements</a>";
    echo "<div>";
    echo "<ul>";
    echo "<li class='divider'></li>";
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
    echo "<li><a href='/forum.php'>Forum Index</a></li>";
    echo "<li><a href='/viewforum.php?f=25'>Event Forums</a></li>";
    echo "<li><a href='/forumposthistory.php'>Recent Forum Posts</a></li>";
    echo "<li class='divider'></li>";	
    echo "<li><a href='/userList.php'>User List</a></li>";
    echo "<li><a href='/globalRanking.php'>Global Ranking</a></li>";
    echo "<li><a href='/recentMastery.php'>Recent Masteries</a></li>";
    // echo "<li><a href='/leaderboardList.php'>Leaderboards</a></li>";
    echo "<li><a href='/developerstats.php'>Developer Stats</a></li>";
    echo "<li class='divider'></li>";
    echo "<li><a href='https://news.retroachievements.org/'>RANews</li>";
    echo "<li><a href='https://github.com/RetroAchievements/guides/wiki/'>RAGuides Wiki</li>";
    echo "<li class='divider'></li>";
    echo "<li><a href='https://docs.retroachievements.org/'>User Documentation</a></li>";
    echo "<li><a href='https://docs.retroachievements.org/Developer-docs/'>Developer Documentation</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</li>";

    echo "<li><a href='/download.php'>Download</a></li>";

    if ($user) {
        echo "<li><a href='#'>My Pages</a>";
        echo "<div>";
        echo "<ul>";
        echo "<li><a href='/user/$username'>Profile</a></li>";
        echo "<li><a href='/gameList.php?d=$username'>My Sets</a></li>";
        echo "<li><a href='/ticketmanager.php?u=$username'>My Tickets</a></li>";
        echo "<li><a href='/claimlist.php?u=$username'>My Claims</a></li>";
        echo "<li><a href='/achievementList.php?s=14&p=1'>Achievements</a></li>";
        echo "<li><a href='/friends.php'>Following</a></li>";
        echo "<li><a href='/history.php'>History</a></li>";
        echo "<li><a href='/inbox.php'>Messages</a></li>";
        echo "<li><a href='/setRequestList.php?u=$username'>Requested Sets</a></li>";
        echo "<li class='divider'></li>";
        echo "<li><a href='/controlpanel.php'>Settings</a></li>";
        echo "<li class='divider'></li>";
        echo "<li>";
        echo "<form action='/request/auth/logout.php' method='post'>";
        echo csrf_field();
        echo "<button class='p-0 bg-transparent text-gray-200 border-0 w-full'>Logout</button>";
        echo "</form>";
        echo "</li>";
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
        echo "<li><a href='/expiringclaims.php?'>Expiring Claims</a></li>";
        echo "<li class='divider'></li>";
        echo "<li><a href='/latesthasheslinked.php'>Latest Linked Hashes</a></li>";
        // Admin
        if ($permissions >= Permissions::Admin) {
            echo "<li class='divider'></li>";
            echo "<li><a href='/viewforum.php?f=0'>Invalid Forum Posts</a></li>";
            echo "<li><a href='/admin.php'>Admin Tools</a></li>";
        }
        if (auth()->user()->can('viewLogs')) {
            echo "<li class='divider'></li>";
            echo "<li><a href='" . route('blv.index') . "'>Logs</a></li>";
        }
        echo "</ul>";
        echo "</div>";
        echo "</li>";
    }

    echo "</ul>";

    $searchQuery = null;
    if ($_SERVER['SCRIPT_NAME'] === '/searchresults.php') {
        $searchQuery = attributeEscape(request()->query('s'));
    }
    echo "<form class='flex searchbox-top' action='/searchresults.php'>";
    // echo "Search:&nbsp;";
    echo "<input name='s' type='text' class='flex-1 searchboxinput' value='$searchQuery' placeholder='Search the site...'>";
    echo "<input type='submit' value='🔎︎' title='Search the site'>";
    echo "</form>";
}

function RenderPaginator($numItems, $perPage, $offset, $urlPrefix): void
{
    // floor to current page
    $offset = floor($offset / $perPage) * $perPage;

    if ($offset > 0) {
        echo "<a title='First' href='{$urlPrefix}0'>&#x226A;</a>&nbsp;";

        $prevOffset = $offset - $perPage;
        echo "<a title='Previous' href='$urlPrefix$prevOffset'>&lt;</a>&nbsp;";
    }

    echo "Page <select class='gameselector' onchange='window.location=\"$urlPrefix\" + this.options[this.selectedIndex].value'>";
    $pages = floor(($numItems + $perPage - 1) / $perPage);
    for ($i = 1; $i <= $pages; $i++) {
        $pageOffset = ($i - 1) * $perPage;
        echo "<option value='$pageOffset'" . (($offset == $pageOffset) ? " selected" : "") . ">$i</option>";
    }
    echo "</select> of $pages";

    $nextOffset = $offset + $perPage;
    if ($nextOffset < $numItems) {
        echo "&nbsp;<a title='Next' href='$urlPrefix$nextOffset'>&gt;</a>";

        $lastOffset = $numItems - 1; // 0-based
        $lastOffset = $lastOffset - ($lastOffset % $perPage);
        echo "&nbsp;<a title='Last' href='$urlPrefix$lastOffset'>&#x226B;</a>";
    }
}
