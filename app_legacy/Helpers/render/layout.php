<?php

use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;

// see resources/views/layouts/app.blade.php
// see resources/views/layouts/partials/head.blade.php

function RenderContentStart(?string $pageTitle = null): void
{
    // hijack view variables
    view()->share('pageTitle', $pageTitle);

    // TBD add legacy content wrapper start
}

function RenderContentEnd(): void
{
    // TBD add legacy content wrapper end
}

function RenderOpenGraphMetadata(string $title, ?string $OGType, string $imageURL, string $description): void
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

    $menuSystemsList = [
        [
            "Nintendo" => [
                ["systemName" => "Game Boy", "listID" => 4, "iconName" => "Game Boy"],
                ["systemName" => "Game Boy Color", "listID" => 6, "iconName" => "Game Boy Color"],
                ["systemName" => "Game Boy Advance", "listID" => 5, "iconName" => "Game Boy Advance"],
                ["systemName" => "NES/Famicom", "listID" => 7, "iconName" => "NES"],
                ["systemName" => "SNES/Super Famicom", "listID" => 3, "iconName" => "SNES"],
                ["systemName" => "Nintendo 64", "listID" => 2, "iconName" => "Nintendo 64"],
                ["systemName" => "Nintendo DS", "listID" => 18, "iconName" => "DS"],
                // ["systemName" => "Nintendo DSi", "listID"=>78, "iconName" => "Nintendo DSi"],
                ["systemName" => "Pokemon Mini", "listID" => 24, "iconName" => "Pokemon Mini"],
                ["systemName" => "Virtual Boy", "listID" => 28, "iconName" => "Virtual Boy"],
            ],
            "Sony" => [
                ["systemName" => "PlayStation", "listID" => 12, "iconName" => "PlayStation"],
                ["systemName" => "PlayStation 2", "listID" => 21, "iconName" => "PlayStation 2"],
                ["systemName" => "PlayStation Portable", "listID" => 41, "iconName" => "PlayStation Portable"],
            ],
            "Atari" => [
                ["systemName" => "Atari 2600", "listID" => 25, "iconName" => "Atari 2600"],
                ["systemName" => "Atari 7800", "listID" => 51, "iconName" => "Atari 7800"],
                ["systemName" => "Atari Jaguar", "listID" => 17, "iconName" => "Jaguar"],
                // ["systemName" => "Atari Jaguar CD", "listID"=>77, "iconName" => "Jaguar CD"],
                ["systemName" => "Atari Lynx", "listID" => 13, "iconName" => "Lynx"],
            ],
            "NEC" => [
                ["systemName" => "PC Engine/TurboGrafx-16", "listID" => 8, "iconName" => "PC Engine  TurboGrafx"],
                ["systemName" => "PC Engine CD/TurboGrafx-CD", "listID" => 76, "iconName" => "PC Engine CD"],
                ["systemName" => "PC-8000/8800", "listID" => 47, "iconName" => "PC-80008800"],
                ["systemName" => "PC-FX", "listID" => 49, "iconName" => "PC-FX"],
            ],
        ],
        [
            "Sega" => [
                ["systemName" => "SG-1000", "listID" => 33, "iconName" => "SG-1000"],
                ["systemName" => "Master System", "listID" => 11, "iconName" => "Master System"],
                ["systemName" => "Game Gear", "listID" => 15, "iconName" => "Game Gear"],
                ["systemName" => "Genesis/Mega Drive", "listID" => 1, "iconName" => "Mega Drive  Genesis"],
                ["systemName" => "Sega CD", "listID" => 9, "iconName" => "Sega CD"],
                ["systemName" => "Sega 32X", "listID" => 10, "iconName" => "32X"],
                ["systemName" => "Sega Saturn", "listID" => 39, "iconName" => "Saturn"],
                ["systemName" => "Sega Dreamcast", "listID" => 40, "iconName" => "Dreamcast"],
            ],
            "Other" => [
                ["systemName" => "3DO Interactive Multiplayer", "listID" => 43, "iconName" => "3DO Interactive Multiplayer"],
                ["systemName" => "Amstrad CPC", "listID" => 37, "iconName" => "CPC"],
                ["systemName" => "Apple II", "listID" => 38, "iconName" => "Apple II"],
                ["systemName" => "Arcade", "listID" => 27, "iconName" => "Arcade"],
                ["systemName" => "Arduboy", "listID" => 71, "iconName" => "Arduboy"],
                ["systemName" => "ColecoVision", "listID" => 44, "iconName" => "ColecoVision"],
                ["systemName" => "Fairchild Channel F", "listID" => 57, "iconName" => "Channel F"],
                ["systemName" => "Intellivision", "listID" => 45, "iconName" => "Intellivision"],
                ["systemName" => "Magnavox Odyssey 2", "listID" => 23, "iconName" => "Odyssey 2"],
                ["systemName" => "Mega Duck", "listID" => 69, "iconName" => "Mega Duck"],
                ["systemName" => "MSX", "listID" => 29, "iconName" => "MSX"],
                ["systemName" => "Neo Geo Pocket", "listID" => 14, "iconName" => "Neo Geo Pocket"],
                ["systemName" => "Vectrex", "listID" => 46, "iconName" => "Vectrex"],
                ["systemName" => "WASM-4", "listID" => 72, "iconName" => "WASM-4"],
                ["systemName" => "Watara Supervision", "listID" => 63, "iconName" => "Supervision"],
                ["systemName" => "WonderSwan", "listID" => 53, "iconName" => "WonderSwan"],
            ],

        ],
    ];
    echo "<ul class='flex-1'>";
    echo "<li><a href='#'>Games</a>";
    echo "<div>";
    foreach ($menuSystemsList as $column){
        echo "<ul>";
        foreach ($column as $brand => $systems) {
            echo "<li class='dropdown-header'>$brand</li>";
            foreach ($systems as $system){
                $systemName = $system['systemName'];
                $listId = $system['listID'];
                $iconName = $system['iconName'];
                echo "<li><a href='/gameList.php?c=$listId' class='!flex items-center gap-x-2' >"; // the flex class needs to be forced here
                echo " <img src='assets/images/system/$iconName.png' width='16' height='16' alt='mini console icon'>";
                echo " <span>$systemName</span>";
                echo "</a></li>";
            }
        }
        echo "</ul>";
    }

    echo "<ul>";
    echo "<li class='dropdown-header'>Miscellaneous</li>";
    echo "<li><a href='/gameList.php'>All Games</a></li>";
    echo "<li><a href='/gameSearch.php?p=0'>Hardest Games</a></li>";
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
    echo "<li><a href='/achievementList.php'>All Achievements</a></li>";
    echo "<li class='divider'></li>";
    // echo "<li><a href='/awardedList.php'>Commonly Won Achievements</a></li>";
    echo "<li><a href='/achievementList.php?s=4&p=2'>Easy Achievements</a></li>";
    echo "<li><a href='/achievementList.php?s=14&p=2'>Hardest Achievements</a></li>";
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

function RenderPaginator(int $numItems, int $perPage, int $offset, string $urlPrefix): void
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
    if ($pages < 1) {
        $pages = 1;
    }
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
