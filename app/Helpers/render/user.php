<?php

use App\Site\Models\User;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;

/**
 * Create the user and tooltip div that is shown when you hover over a username or user avatar.
 */
function userAvatar(
    ?string $username,
    ?bool $label = null,
    ?bool $icon = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    ?string $link = null,
    bool|string|array $tooltip = true,
): string {
    if (empty($username)) {
        return '';
    }

    $user = Cache::store('array')->remember(
        CacheKey::buildUserCardDataCacheKey($username),
        Carbon::now()->addMonths(3),
        function () use ($username): ?array {
            $foundUser = User::firstWhere('User', $username);

            return $foundUser ? $foundUser->toArray() : null;
        }
    );

    if (!$user) {
        $userSanitized = $username;
        sanitize_outputs($userSanitized);

        $iconLabel = '';
        if ($icon !== false && ($icon || !$label)) {
            $iconLabel = "<img loading='lazy' width='$iconSize' height='$iconSize' src='" . media_asset('/UserPic/_User.png') . "' title='$userSanitized' alt='$userSanitized' class='$iconClass'>";
        }

        $usernameLabel = '';
        if ($label !== false && ($label || !$icon)) {
            $usernameLabel = "<del>$userSanitized</del>";
        }

        return "<span class='inline whitespace-nowrap'><span class='inline-block'>" . $iconLabel . $usernameLabel . "</span></span>";
    }

    $username = $user['User'] ?? null;

    return avatar(
        resource: 'user',
        id: $username,
        label: $label !== false && ($label || !$icon) ? $username : null,
        link: $link ?: route('user.show', $username),
        tooltip: is_array($tooltip) ? renderUserCard($tooltip) : $tooltip,
        class: 'inline whitespace-nowrap',
        iconUrl: $icon !== false && ($icon || !$label) ? media_asset('/UserPic/' . $username . '.png') : null,
        iconSize: $iconSize,
        iconClass: $iconClass,
    );
}

function renderUserCard(string|array $user): string
{
    return Blade::render('<x-user-card :user="$user" />', [
        'user' => $user,
    ]);
}

function getCompletedAndIncompletedSetsCounts(array $userCompletedGamesList): array
{
    $completedSetsCount = 0;
    $incompletedSetsCount = 0;

    foreach ($userCompletedGamesList as $game) {
        $nextMaxPossible = $game['MaxPossible'];
        $nextNumAwarded = $game['NumAwarded'];

        if ($nextNumAwarded == $nextMaxPossible) {
            $completedSetsCount++;
        } else {
            $incompletedSetsCount++;
        }
    }

    return ['completedSetsCount' => $completedSetsCount, 'incompletedSetsCount' => $incompletedSetsCount];
}

function RenderCompletedGamesList(
    array $userCompletedGamesList,
    string $username,
    bool $isInitiallyHidingCompletedSets = false,
): void {
    echo "<div id='completedgames' class='component' >";

    echo "<h3>Completion Progress</h3>";

    $checkedAttribute = $isInitiallyHidingCompletedSets ? 'checked' : '';
    $setsCounts = getCompletedAndIncompletedSetsCounts($userCompletedGamesList);
    if ($setsCounts['completedSetsCount'] > 0 && $setsCounts['incompletedSetsCount'] > 0) {
        echo <<<HTML
            <label class="flex items-center gap-x-1 mb-2">
                <input 
                    type="checkbox" 
                    id="hide-user-completed-sets-checkbox" 
                    onchange="toggleUserCompletedSetsVisibility()"
                    $checkedAttribute
                >
                    Hide completed games
                </input>
            </label>
        HTML;
    }

    echo "<div id='usercompletedgamescomponent'>";

    echo "<table class='table-highlight'><tbody>";

    $numItems = count($userCompletedGamesList);
    for ($i = 0; $i < $numItems; $i++) {
        $nextMaxPossible = $userCompletedGamesList[$i]['MaxPossible'];

        $nextNumAwarded = $userCompletedGamesList[$i]['NumAwarded'];
        if ($nextNumAwarded == 0 || $nextMaxPossible == 0) { // Ignore 0 (div by 0 anyway)
            continue;
        }

        $pctAwardedNormal = ($nextNumAwarded / $nextMaxPossible) * 100.0;

        $nextNumAwardedHC = $userCompletedGamesList[$i]['NumAwardedHC'] ?? 0;
        $pctAwardedHCProportional = ($nextNumAwardedHC / $nextNumAwarded) * 100.0; // This is given as a proportion of normal completion!
        $nextTotalAwarded = max($nextNumAwardedHC, $nextNumAwarded); // Just take largest

        if (!isset($nextMaxPossible)) {
            continue;
        }

        $isCompletedClassName = ($pctAwardedNormal == 100)
            ? "completion-progress-completed-row" . ($isInitiallyHidingCompletedSets ? " hidden" : "")
            : '';

        echo "<tr class='$isCompletedClassName'>";

        echo "<td class='py-2'>";
        echo Blade::render('
            <x-game.multiline-avatar
                :gameId="$gameId"
                :gameTitle="$gameTitle"
                :gameImageIcon="$gameImageIcon"
                :consoleName="$consoleName"
            />
        ', [
            'gameId' => $userCompletedGamesList[$i]['GameID'],
            'gameTitle' => $userCompletedGamesList[$i]['Title'],
            'gameImageIcon' => $userCompletedGamesList[$i]['ImageIcon'],
            'consoleName' => $userCompletedGamesList[$i]['ConsoleName'],
        ]);
        echo "</td>";
        echo "<td>";

        echo "<div class='w-24'>";
        echo "<div class='flex w-full items-center'>";
        echo "<div class='progressbar grow'>";
        echo "<div class='completion' style='width:$pctAwardedNormal%'>";
        echo "<div class='completion-hardcore' style='width:$pctAwardedHCProportional%' title='Hardcore: $nextNumAwardedHC/$nextMaxPossible'></div>";
        echo "</div>";
        echo "</div>";
        echo renderCompletionIcon((int) $nextTotalAwarded, (int) $nextMaxPossible, $pctAwardedHCProportional, tooltip: true);
        echo "</div>";
        echo "<div class='progressbar-label pr-5 -mt-1'>";
        echo "$nextTotalAwarded of $nextMaxPossible";
        echo "</div>";
        echo "</div>";

        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "</div>";
}
