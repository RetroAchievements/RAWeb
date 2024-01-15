<?php

use App\Site\Enums\Permissions;
use Illuminate\Support\Facades\Blade;

function gameAvatar(
    int|string|array $game,
    ?bool $label = null,
    bool|string|null $icon = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    bool $tooltip = true,
    ?string $context = null,
    ?string $title = null,
): string {
    $id = $game;

    if (is_array($game)) {
        $id = $game['GameID'] ?? $game['ID'];

        if ($label !== false) {
            if ($title === null) {
                $title = $game['GameTitle'] ?? $game['Title'] ?? null;

                $consoleName = $game['Console'] ?? $game['ConsoleName'] ?? null;
                if ($consoleName) {
                    $title .= " ($consoleName)";
                }
            }

            sanitize_outputs($title);   // sanitize before rendering HTML
            $label = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $title]);
            $label = preg_replace('/\s+/', ' ', $label);
        }

        if ($icon === null) {
            $icon = media_asset($game['GameIcon'] ?? $game['ImageIcon']);
        }
    }

    return avatar(
        resource: 'game',
        id: $id,
        label: $label !== false && ($label || !$icon) ? $label : null,
        link: route('game.show', $id),
        tooltip: $tooltip,
        iconUrl: $icon !== false && ($icon || !$label) ? $icon : null,
        iconSize: $iconSize,
        iconClass: $iconClass,
        context: $context,
        sanitize: $title === null,
        altText: $title ?? (is_string($label) ? $label : null),
    );
}

/**
 * Render game breadcrumb prefix, with optional link on last crumb
 *
 * Format: `All Games » (console) » (game title)`.
 * If given data is for a subset, then `» Subset - (name)` is also added.
 */
function renderGameBreadcrumb(array|int $data, bool $addLinkToLastCrumb = true): string
{
    if (is_int($data)) {
        $data = getGameData($data);
    }
    // TODO refactor to Game
    $consoleID = $data['ConsoleID'];
    $consoleName = $data['ConsoleName'];

    // Return next crumb (i.e `» text`), either as a link or not
    $nextCrumb = fn ($text, $href = ''): string => " &raquo; " . ($href ? "<a href='$href'>$text</a>" : "<span class='font-bold'>$text</span>");

    // Retrieve separate IDs and titles for main game and subset (if any)
    $getSplitData = function ($data) use ($consoleID): array {
        $gameID = $data['GameID'] ?? $data['ID'];
        $gameTitle = $data['GameTitle'] ?? $data['Title'];
        // Match and possibly split main title and subset
        $mainID = $gameID;
        $mainTitle = $gameTitle;
        $matches = [];
        if (preg_match('/(.+)(\[Subset - .+\])/', $gameTitle, $matches)) {
            $mainTitle = trim($matches[1]);
            $subset = $matches[2];
            $mainID = getGameIDFromTitle($mainTitle, $consoleID);
            $subsetID = $gameID;
            $renderedSubset = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $subset]);
        }

        $renderedMain = Blade::render('
            <x-game-title
                :rawTitle="$rawTitle"
                :showTags="$showTags"
            />', [
                'rawTitle' => $mainTitle,
                'showTags' => false,
            ]
        );

        if ($renderedMain !== $mainTitle) {
            // In the rare case of a same-console derived game sharing identical
            // title with a base one, include category to solve ambiguity
            $baseTitle = trim(substr($mainTitle, strrpos($mainTitle, '~') + 1));
            $baseID = getGameIDFromTitle($baseTitle, $consoleID);
            if ($baseID) {
                $renderedMain = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $mainTitle]);
            }
        }

        return [$mainID, $renderedMain, $subsetID ?? null, $renderedSubset ?? null];
    };

    $html = "<a href='/gameList.php'>All Games</a>"
        . $nextCrumb($consoleName, route('system.game.index', ['system' => $consoleID]));

    [$mainID, $renderedMain, $subsetID, $renderedSubset] = $getSplitData($data);
    $baseHref = (($addLinkToLastCrumb || $subsetID) && $mainID) ? "/game/$mainID" : '';
    $html .= $nextCrumb($renderedMain, $baseHref);
    if ($subsetID) {
        $html .= $nextCrumb($renderedSubset, $addLinkToLastCrumb ? "/game/$subsetID" : '');
    }

    return $html;
}

function renderGameCard(int|array $game, ?string $targetUsername): string
{
    $gameId = is_int($game) ? $game : ($game['GameID'] ?? $game['ID'] ?? null);

    if (empty($gameId)) {
        return __('legacy.error.error');
    }

    return Blade::render('<x-game-card :gameId="$gameId" :targetUsername="$targetUsername" />', [
        'gameId' => $gameId,
        'targetUsername' => $targetUsername,
    ]);
}

function RenderGameSort(
    bool $isFullyFeaturedGame,
    ?int $flag,
    int $officialFlag,
    int $gameID,
    ?int $sortBy,
    bool $canSortByType = false,
): void {
    echo "<div><span>";
    echo "Sort: ";

    $flagParam = ($flag != $officialFlag) ? "f=$flag" : '';

    $sortType = ($sortBy < 10) ? "^" : "<sup>v</sup>";
    // Used for options which sort in Descending order on first click
    $sortReverseType = ($sortBy >= 10) ? "^" : "<sup>v</sup>";

    $sort1 = ($sortBy == 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;
    $sort4 = ($sortBy == 4) ? 14 : 4;
    $sort5 = ($sortBy == 5) ? 15 : 5;
    $sort6 = ($sortBy == 6) ? 16 : 6;

    $mark1 = ($sortBy % 10 == 1) ? "&nbsp;$sortType" : "";
    $mark2 = ($sortBy % 10 == 2) ? "&nbsp;$sortType" : "";
    $mark3 = ($sortBy % 10 == 3) ? "&nbsp;$sortType" : "";
    $mark4 = ($sortBy % 10 == 4) ? "&nbsp;$sortType" : "";
    $mark5 = ($sortBy % 10 == 5) ? "&nbsp;$sortType" : "";
    $mark6 = ($sortBy % 10 == 6) ? "&nbsp;$sortType" : "";

    $reverseMark1 = ($sortBy % 10 == 1) ? "&nbsp;$sortReverseType" : "";
    $reverseMark2 = ($sortBy % 10 == 2) ? "&nbsp;$sortReverseType" : "";
    $reverseMark3 = ($sortBy % 10 == 3) ? "&nbsp;$sortReverseType" : "";
    $reverseMark4 = ($sortBy % 10 == 4) ? "&nbsp;$sortReverseType" : "";
    $reverseMark5 = ($sortBy % 10 == 5) ? "&nbsp;$sortReverseType" : "";
    $reverseMark6 = ($sortBy % 10 == 6) ? "&nbsp;$sortReverseType" : "";

    if ($isFullyFeaturedGame) {
        echo "<a href='/game/$gameID?$flagParam&s=$sort1'>Normal$mark1</a> - ";
        echo "<a href='/game/$gameID?$flagParam&s=$sort2'>Won By$mark2</a> - ";
        // TODO sorting by "date won" isn't implemented yet.
        // if(isset($user)) {
        //    echo "<a href='/game/$gameID?$flagParam&s=$sort3'>Date Won$mark3</a> - ";
        // }
        echo "<a href='/game/$gameID?$flagParam&s=$sort4'>Points$mark4</a> - ";
        echo "<a href='/game/$gameID?$flagParam&s=$sort5'>Title$mark5</a>";
        if ($canSortByType) {
            echo " - ";
            echo "<a href='/game/$gameID?$flagParam&s=$sort6'>Type$mark6</a>";
        }
    } else {
        echo "<a href='/game/$gameID?$flagParam&s=$sort1'>Default$mark1</a> - ";
        echo "<a href='/game/$gameID?$flagParam&s=$sort2'>Retro Points$reverseMark2</a>";
    }

    echo "<sup>&nbsp;</sup></span></div>";
}

function RenderGameAlts(array $gameAlts, ?string $headerText = null): void
{
    echo "<div class='component gamealts'>";
    if ($headerText) {
        echo "<h2 class='text-h3'>$headerText</h2>";
    }
    echo "<table class='table-highlight'><tbody>";
    foreach ($gameAlts as $nextGame) {
        $consoleName = $nextGame['ConsoleName'];
        $points = $nextGame['Points'];
        $totalTP = $nextGame['TotalTruePoints'];
        $points = (int) $points;
        $totalTP = (int) $totalTP;

        $isFullyFeaturedGame = $consoleName != 'Hubs';
        if (!$isFullyFeaturedGame) {
            $consoleName = null;
        }

        echo Blade::render('
            <x-game.similar-game-table-row
                :gameId="$gameId"
                :gameTitle="$gameTitle"
                :gameImageIcon="$gameImageIcon"
                :consoleName="$consoleName"
                :totalPoints="$totalPoints"
                :totalRetroPoints="$totalRetroPoints"
                :isFullyFeaturedGame="$isFullyFeaturedGame"
            />
        ', [
            'gameId' => $nextGame['gameIDAlt'],
            'gameTitle' => $nextGame['Title'],
            'gameImageIcon' => $nextGame['ImageIcon'],
            'consoleName' => $consoleName,
            'totalPoints' => $points,
            'totalRetroPoints' => $totalTP,
            'isFullyFeaturedGame' => $isFullyFeaturedGame,
        ]);
    }

    echo "</tbody></table>";
    echo "</div>";
}

function RenderLinkToGameForum(string $gameTitle, int $gameID, ?int $forumTopicID, int $permissions = Permissions::Unregistered): void
{
    sanitize_outputs(
        $gameTitle,
    );

    if (!empty($forumTopicID) && getTopicDetails($forumTopicID)) {
        echo "<a class='btn py-2 mb-2 block' href='/viewtopic.php?t=$forumTopicID'><span class='icon icon-md ml-1 mr-3'>💬</span>Official Forum Topic</a>";
    } else {
        if ($permissions >= Permissions::Developer) {
            echo "<form action='/request/game/generate-forum-topic.php' method='post' onsubmit='return confirm(\"Are you sure you want to create the official forum topic for this game?\")'>";
            echo csrf_field();
            echo "<input type='hidden' name='game' value='$gameID'>";
            echo "<button class='btn btn-link py-2 mb-2 w-full'><span class='icon icon-md ml-1 mr-3'>💬</span>Create Forum Topic</button>";
            echo "</form>";
        }
    }
}

/**
 * Render completion icon, given that player achieved 100% set progress
 */
function renderCompletionIcon(
    int $awardedCount,
    int $totalCount,
    float|string $hardcoreRatio,
    bool $tooltip = false,
): string {
    if ($awardedCount === 0 || $awardedCount < $totalCount) {
        return "<div class='completion-icon'></div>";
    }
    [$icon, $class] = $hardcoreRatio == 100.0 ? ['👑', 'mastered'] : ['🎖️', 'completed'];
    $class = "completion-icon $class";
    $tooltipText = '';
    if ($tooltip) {
        $tooltipText = $hardcoreRatio == 100.0 ? 'Mastered (hardcore)' : 'Completed';
        $class .= ' tooltip';
    }

    return "<div class='$class' title='$tooltipText'>$icon</div>";
}
