<?php

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Site\Enums\Permissions;
use App\Support\Cache\CacheKey;
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

    $userCardDataCacheKey = CacheKey::buildUserCardDataCacheKey($username);
    $user = Cache::store('array')->rememberForever($userCardDataCacheKey, function () use ($username) {
        getAccountDetails($username, $data);

        return $data;
    });

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
    $username = is_string($user) ? $user : ($user['User'] ?? null);

    if (empty($username)) {
        return __('legacy.error.error');
    }

    $data = [];
    if (is_array($user)) {
        $data = $user;
    }

    if (empty($data)) {
        $userCardDataCacheKey = CacheKey::buildUserCardDataCacheKey($username);
        $data = Cache::store('array')->rememberForever($userCardDataCacheKey, function () use ($username) {
            getAccountDetails($username, $dataOut);

            return $dataOut;
        });
    }

    // deleted users
    if (empty($data)) {
        return '';
    }

    $userMotto = $data['Motto'];
    $userHardcorePoints = $data['RAPoints'];
    $userSoftcorePoints = $data['RASoftcorePoints'];
    $userTruePoints = $data['TrueRAPoints'];
    $userAccountType = Permissions::toString($data['Permissions']);
    $userUntracked = $data['Untracked'];
    $lastLogin = $data['LastLogin'] ? getNiceDate(strtotime($data['LastLogin'])) : null;
    $memberSince = $data['Created'] ? getNiceDate(strtotime($data['Created']), true) : null;

    $tooltip = "<div class='tooltip-body flex items-start gap-2 p-2' style='width: 400px'>";

    $tooltip .= "<img width='128' height='128' src='" . media_asset('/UserPic/' . $username . '.png') . "'>";

    $tooltip .= "<div class='grow' style='font-size: 8pt'>";

    $tooltip .= "<div class='flex justify-between mb-2'>";
    $tooltip .= "<div class='usercardusername'>$username</div>";
    $tooltip .= "<div class='usercardaccounttype'>$userAccountType</div>";
    $tooltip .= "</div>";

    // Add the user motto if it's set
    if ($userMotto !== null && mb_strlen($userMotto) > 2) {
        sanitize_outputs($userMotto);
        $tooltip .= "<div class='usermotto mb-1'>$userMotto</div>";
    }

    // Add the user points if there are any
    if ($userHardcorePoints > $userSoftcorePoints) {
        $tooltip .= "<div class='usercardbasictext'><b>Points:</b> $userHardcorePoints ($userTruePoints)</div>";
        $userRank = $userHardcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($username, RankType::Hardcore);
        $userRankLabel = 'Site Rank';
    } elseif ($userSoftcorePoints > 0) {
        $tooltip .= "<div class='usercardbasictext'><b>Softcore Points:</b> $userSoftcorePoints</div>";
        $userRank = $userSoftcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($username, RankType::Softcore);
        $userRankLabel = 'Softcore Rank';
    } else {
        $tooltip .= "<div class='usercardbasictext'><b>Points:</b> 0</div>";
        $userRank = 0;
        $userRankLabel = 'Site Rank';
    }

    // Add the other user information
    if ($userUntracked) {
        $tooltip .= "<div class='usercardbasictext'><b>$userRankLabel:</b> Untracked</div>";
    } elseif ($userRank == 0) {
        $tooltip .= "<div class='usercardbasictext'><b>$userRankLabel:</b> Needs at least " . Rank::MIN_POINTS . " points </div>";
    } else {
        $tooltip .= "<div class='usercardbasictext'><b>$userRankLabel:</b> $userRank</div>";
    }

    if ($lastLogin) {
        $tooltip .= "<div class='usercardbasictext'><b>Last Activity:</b> $lastLogin</div>";
    }
    if ($memberSince) {
        $tooltip .= "<div class='usercardbasictext'><b>Member Since:</b> $memberSince</div>";
    }
    $tooltip .= "</div>";
    $tooltip .= "</div>";

    return $tooltip;
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

function RenderCompletedGamesList(array $userCompletedGamesList, bool $isInitiallyHidingCompletedSets = false): void
{
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

        echo "<td>";
        echo gameAvatar($userCompletedGamesList[$i], label: false);
        echo "</td>";
        echo "<td class='smaller'>";
        echo gameAvatar($userCompletedGamesList[$i], icon: false);
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
