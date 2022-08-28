<?php

function RenderDocsComponent(): void
{
    echo "
      <div class='component' style='text-align: center'>
        <div id='docsbox' class='infobox'>
          <div>
            <a href='https://docs.retroachievements.org/'>📘 Documentation</a> & <a href='https://docs.retroachievements.org/FAQ/' rel='noopener'>FAQ</a>.
          </div>
        </div>
      </div>";
}

function RenderCurrentlyOnlineComponent(): void
{
    echo "<div class='component'>";
    echo "<h3>Currently Online</h3>";
    echo "<div id='playersonlinebox' class='infobox'>";
    $numPlayers = count(getCurrentlyOnlinePlayers());
    echo "<div>There are currently <strong>$numPlayers</strong> players online.</div>";
    echo "</div>";
    echo "<div style='min-height: 160px;' id='chart_usersonline'></div>";
    echo "<div class='text-right lastupdatedtext'><small><span id='playersonline-update'></span></small></div>";
    echo "</div>";
}

function RenderActivePlayersComponent(): void
{
    echo <<<HTML
        <div id='active-players-component' class='component activeplayerscomponent'>
            <h3>Active Players</h3>
            <div id='playersNotice' style='margin-bottom: 7px'>
                <span style='margin-bottom: 5px; display: inline-block;'>
                    There are <strong data-bind="text: numberOfFilteredPlayers"></strong> <span data-bind='visible: usersAreFiltered'>filtered</span> active players<span data-bind='visible: usersAreFiltered'> (out of <strong data-bind='text: numberOfPlayersActive'></strong> total)</span>.
                </span>
                <a class='float-right' id='active-players-menu-button' href='#!' data-bind='click: OnActivePlayersMenuButtonClick, css: { menuOpen: shouldMenuBeVisible }'></a>
                <div id='active-player-menu' data-bind='visible: shouldMenuBeVisible'>
                    <div>
                        <input type='text' style='width: 100%;' placeholder='Filter by player, game, console, or Rich Presence...' data-bind='value: playerFilterText, valueUpdate: "input"' />
                    </div>
                    <div id='active-players-filter-options'>
                        <label><input type='checkbox' data-bind='checked: rememberFiltersValue' /> Remember My Filter</label>
                    </div>
                </div>
            </div>
            <div id='activeplayersbox' style='min-height: 54px'>
                <table data-bind='hidden: isLoading'>
                    <thead>
                        <th>User</th>
                        <th>Game</th>
                        <th>Currently...</th>
                    </thead>
                    <tbody>
                        <!-- ko foreach: filteredPlayers -->
                        <tr>
                            <td data-bind='html: playerHtml'></td>
                            <td data-bind='html: gameHtml'></td>
                            <td data-bind='text: richPresence'></td>
                        </tr>
                        <!-- /ko -->

                        <tr data-bind='visible: filteredPlayers().length === 0'>
                            <td colspan='3'>No players could be found.</td>
                        </tr>
                    </tbody>
                </table>
                <span data-bind='visible: isLoading'>Loading players...</span>
                <span data-bind='visible: hasError'>An error has occurred while loading players.</span>
            </div>
            <div class='float-right lastupdatedtext'>
                <small id='activeplayers-update' data-bind='text: lastUpdateRender'></small>
            </div>
        </div>
    HTML;

    if (app()->environment('local')) {
        echo '<script type="text/javascript" src="/js/activePlayersBootstrap.js?' . random_int(0, mt_getrandmax()) . '"></script>';
    } else {
        echo '<script type="text/javascript" src="/js/activePlayersBootstrap-' . config('app.version') . '.js"></script>';
    }
}

function RenderAOTWComponent($achID, $forumTopicID): void
{
    $achData = [];
    if (!getAchievementMetadata($achID, $achData)) {
        return;
    }

    echo "<div class='component'>";
    echo "<h3>Achievement of the Week</h3>";

    /**
     * id attribute used for scraping. NOTE: this will be deprecated. Use API_GetAchievementOfTheWeek instead
     */
    echo "<div class='text-center'>";

    $gameID = $achData['GameID'];
    $gameTitle = $achData['GameTitle'];
    $gameIcon = $achData['GameIcon'];
    $consoleName = $achData['ConsoleName'];

    $achID = $achData['AchievementID'];
    $achTitle = $achData['AchievementTitle'];
    $achDesc = $achData['Description'];
    $achBadgeName = $achData['BadgeName'];
    $achPoints = $achData['Points'];
    $achTruePoints = $achData['TrueRatio'];

    sanitize_outputs(
        $gameTitle,
        $consoleName,
        $achTitle,
        $achDesc,
    );

    echo "<div>";
    echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, true);
    echo "</div>";
    echo "in";
    echo "<div>";
    echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 24);
    echo "</div>";
    echo "<a class='btn btn-link' href='/viewtopic.php?t=$forumTopicID'>Join this tournament!</a>";

    echo "</div>";
    echo "</div>";
}

function RenderConsoleMessage(int $consoleId): void
{
    // PS2
    if ($consoleId === 21) {
        echo <<<HTML
            <div style="margin-bottom: 10px">
                <a href="/viewtopic.php?t=11108" class="btn btn-link">⚠️️ Achievement developers are currently involved in a PlayStation 2 rollout. There is no <abbr title="Estimated time of arrival">ETA</abbr> at this time. Click for more details. ⚠️</a>
            </div>
        HTML;
    }
}
