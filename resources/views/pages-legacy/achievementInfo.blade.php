<?php

// TODO migrate to AchievementController::show()

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Services\TriggerDecoderService;
use App\Support\Shortcode\Shortcode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;

authenticateFromCookie($user, $permissions, $userDetails);

$userModel = User::whereName($user)->first();

$achievementID = (int) request('achievementId');
if (empty($achievementID)) {
    abort(404);
}

$achievementModel = Achievement::find($achievementID);

$dataOut = GetAchievementData($achievementID);
if (empty($dataOut)) {
    abort(404);
}

$achievementTitle = $dataOut['Title'];
$desc = $dataOut['Description'];
$achFlags = (int) $dataOut['Flags'];
$achPoints = (int) $dataOut['Points'];
$achType = $dataOut['type'];
$gameTitle = $dataOut['GameTitle'];
$badgeName = $dataOut['BadgeName'];
$consoleID = $dataOut['ConsoleID'];
$consoleName = $dataOut['ConsoleName'];
$gameID = $dataOut['GameID'];
$embedVidURL = $dataOut['AssocVideo'];
$author = $dataOut['Author'];
$dateCreated = $dataOut['DateCreated'];
$dateModified = $dataOut['DateModified'];
$achMem = $dataOut['MemAddr'];

$achievementTitleRaw = $dataOut['AchievementTitle'];
$achievementDescriptionRaw = $dataOut['Description'];
$gameTitleRaw = $dataOut['GameTitle'];

$game = Game::find($dataOut['GameID']);
$isEventGame = $game->system_id === System::Events;

sanitize_outputs(
    $achievementTitle,
    $desc,
    $gameTitle,
    $consoleName,
    $author
);

$numWinners = 0;
$numWinnersHardcore = 0;
$numPossibleWinners = 0;

$unlocks = getAchievementUnlocksData(
    $achievementID,
    $user,
    $numWinners,
    $numWinnersHardcore,
    $numPossibleWinners,
    0,
    50
);

$trackedUnlocksUsers = User::whereIn('display_name', $unlocks->pluck('User')->unique())
    ->where('Untracked', false)
    ->pluck('display_name');

$unlocks = $unlocks->filter(fn ($unlock) => $trackedUnlocksUsers->contains($unlock['User']));

$dataOut['NumAwarded'] = $numWinners;
$dataOut['NumAwardedHardcore'] = $numWinnersHardcore;

$dateWonLocal = "";
foreach ($unlocks as $userObject) {
    if ($userObject['User'] == $user) {
        $dateWonLocal = $userObject['DateAwarded'];

        if ($userObject['HardcoreMode'] === 1) {
            $dataOut['DateEarnedHardcore'] = $dateWonLocal;
        } else {
            $dataOut['DateEarned'] = $dateWonLocal;
        }

        break;
    }
}

if ($dateWonLocal === "" && isset($user)) {
    if ($userModel) {
        $playerAchievement = PlayerAchievement::where('user_id', $userModel->id)
            ->where('achievement_id', $achievementID)
            ->first();
        if ($playerAchievement) {
            if ($playerAchievement->unlocked_hardcore_at) {
                $dateWonLocal = $playerAchievement->unlocked_hardcore_at->__toString();
                $dataOut['DateEarnedHardcore'] = $dateWonLocal;
            } elseif ($playerAchievement->unlocked_at) {
                $dateWonLocal = $playerAchievement->unlocked_at->__toString();
                $dataOut['DateEarned'] = $dateWonLocal;
            }
        }
    }
}

$achievedLocal = ($dateWonLocal !== "");

$eventAchievement = null;
if ($game->system->id === System::Events) {
    $eventAchievement = EventAchievement::where('achievement_id', '=', $achievementID)
        ->with('sourceAchievement')->first();

    if ($eventAchievement?->sourceAchievement) {
        if ($eventAchievement->active_from > Carbon::now()) {
            // future event has been picked. don't show it until it's active
            $eventAchievement = null;
            $badgeName = $dataOut['BadgeName'] = '00000';
            $achievementTitleRaw = $achievementTitle = $dataOut['Title'] = 'Upcoming Challenge';
            $achievementDescriptionRaw = $dataOut['Description'] = '?????';
            $dataOut['SourceGameId'] = null;
        } else {
            // update the ID of the dataOut so the link goes to the source achievement
            $dataOut['ID'] = $eventAchievement->sourceAchievement->id;
        }
    }
}

?>
<x-app-layout
    pageTitle="{!! $achievementTitleRaw !!} in {!! $gameTitleRaw !!} ({{ $consoleName }})"
    :pageDescription="generateAchievementMetaDescription($achievementDescriptionRaw, $achType, $gameTitleRaw, $consoleName, $achPoints, $numWinners)"
    :pageImage="media_asset('/Badge/' . $badgeName . '.png')"
    pageType="retroachievements:achievement"
>
<?php if ($achievedLocal && !$isEventGame): ?>
    <script>
    function ResetProgress() {
        if (confirm('Are you sure you want to reset this progress?')) {
            showStatusMessage('Updating...');

            $.post('/request/user/reset-achievements.php', {
                achievement: <?= $achievementID ?>
            })
                .done(function () {
                    location.reload();
                });
        }
    }
    </script>
<?php endif ?>
    <div id="achievement">
    <div class="navpath">
        <?= renderGameBreadcrumb($dataOut); ?> &raquo; <b><x-achievement.title :rawTitle="$achievementTitle" /></b>
    </div>
    <x-game.heading
        :consoleId="$consoleID"
        :consoleName="$consoleName"
        :gameTitle="$gameTitle"
    />
    <x-game.achievements-list.achievements-list-item
        :achievement="$dataOut"
        :totalPlayerCount="$numPossibleWinners"
        :isUnlocked="$achievedLocal"
    />
    <?php if ($eventAchievement && $eventAchievement->sourceAchievement): ?>
        <div class="ml-4 mb-2">
            From:
            <x-game.multiline-avatar
                :gameId="$eventAchievement->sourceAchievement->game->id"
                :gameTitle="$eventAchievement->sourceAchievement->game->title"
                :gameImageIcon="$eventAchievement->sourceAchievement->game->image_icon_asset_path"
                :consoleId="$eventAchievement->sourceAchievement->game->system->id"
                :consoleName="$eventAchievement->sourceAchievement->game->system->name"
            />
        </div>
    <?php endif ?>
    <?php
    if ($game->system->id !== System::Events) {
        $niceDateCreated = date("d M, Y H:i", strtotime($dateCreated));
        $niceDateModified = date("d M, Y H:i", strtotime($dateModified));

        echo "<p class='embedded smalldata mb-3'>";
        echo "<small>";
        if ($achFlags === Achievement::FLAG_UNPROMOTED) {
            echo "<b>Unofficial Achievement</b><br>";
        }
        echo "Created by " . userAvatar($author, icon: false) . " on: $niceDateCreated<br>";

        if (isset($achievementModel->activeMaintainer) && $author !== $achievementModel->activeMaintainer?->user?->display_name) {
            $maintainer = $achievementModel->activeMaintainer;
            $niceMaintainerStart = date("d M, Y H:i", strtotime($maintainer->effective_from));
            echo "Maintained by " . userAvatar($maintainer->user, icon: false) . " since: $niceMaintainerStart<br>";
        }

        echo "Last modified: $niceDateModified<br>";

        echo "</small>";
        echo "</p>";

        if (isset($user) && $permissions >= Permissions::Registered && System::isGameSystem($consoleID)) {
            $countTickets = countOpenTicketsByAchievement($achievementID);
            echo "<div class='flex justify-between mb-2'>";
            if ($countTickets > 0) {
                echo "<a href='" . route('achievement.tickets', ['achievement' => $achievementID]) ."'>$countTickets open " . mb_strtolower(__res('ticket', $countTickets)) . "</a>";
            } else {
                echo "<i>No open tickets</i>";
            }
            if ($userModel?->can('create', Ticket::class)) {
                echo "<a class='btn btn-link' href='" . route('achievement.report-issue.index', ['achievement' => $achievementID]) ."'>Report an issue</a>";
            }
            echo "</div>";
        }
    } else if ($eventAchievement?->active_from && $eventAchievement?->active_until) {
        echo "<p class='inline smalldate ml-3 mb-2'>Active from ";
        echo Blade::render("<x-date :value=\"\$value\" />", ['value' => $eventAchievement->active_from]);
        echo " - ";
        echo Blade::render("<x-date :value=\"\$value\" />", ['value' => $eventAchievement->active_until->clone()->subDays(1)]);
        echo "</p>";
    }

    if ($achievedLocal && !$isEventGame) {
        echo "<div class='devbox mb-3'>";
        echo "<span onclick=\"$('#resetboxcontent').toggle(); return false;\">Reset Progress ▼</span>";
        echo "<div id='resetboxcontent' style='display: none'>";
        echo "<button class='btn btn-danger' type='button' onclick='ResetProgress()'>Reset this achievement</button>";
        echo "</div></div>";
    }
    echo "<br>";

    if ($userModel && $userModel->can('update', $achievementModel)) {
        echo '<a class="btn mb-1" href="' . route('filament.admin.resources.achievements.edit', ['record' => $achievementModel->id]) . '">Manage</a>';
    } elseif ($userModel && $userModel->can('manage', $achievementModel)) {
        echo '<a class="btn mb-1" href="' . route('filament.admin.resources.achievements.view', ['record' => $achievementModel->id]) . '">Manage</a>';
    }

    if (isset($user) && $permissions >= Permissions::JuniorDeveloper && !$isEventGame) {
        echo "<div class='devbox mb-3'>";
        echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev ▼</span>";
        echo "<div id='devboxcontent' style='display: none'>";

        $len = strlen($achMem);
        if ($len == 65535) {
            echo "<li>Mem:<span class='text-danger'> ⚠️ Max length definition is likely truncated and may not function as expected ⚠️ </span></li>";
        } else {
            echo "<li>Mem:</li>";
        }

        echo "<code>" . htmlspecialchars($achMem) . "</code>";
        echo "<li>Mem explained:</li>";

        $triggerDecoderService = new TriggerDecoderService();
        $groups = $triggerDecoderService->decode($achMem);
        $triggerDecoderService->addCodeNotes($groups, $gameID);

        echo Blade::render("<x-trigger.viewer :groups=\"\$groups\" />",
            ['groups' => $groups]
        );

        echo "</div>"; // devboxcontent
        echo "</div>"; // devbox
    }

    if (!empty($embedVidURL)) {
        echo "<div class='mb-4'>";
        echo Shortcode::render($embedVidURL, ['imgur' => true]);
        echo "</div>";
    }

    if (!$isEventGame) {
        echo "<div class='mb-4'>";
            echo Blade::render("<x-comment.list :articleType=\"\$articleType\" :articleId=\"\$articleId\" />",
                ['articleType' => ArticleType::Achievement, 'articleId' => $achievementID]
            );
        echo "</div>";
    }

    echo "</div>"; // achievement

    /*
     * id attribute used for scraping. NOTE: this will be deprecated. Use API_GetAchievementUnlocks instead
     */
    echo "<div>";
    echo "<h3>Recent Unlocks</h3>";
    if ($unlocks->isEmpty()) {
        echo "Nobody yet! Will you be the first?!<br>";
    } else {
        $userWinners = $unlocks->pluck('User')->toArray();
        $usersMap = [];

        if (!empty($userWinners)) {
            $users = User::whereIn('display_name', $userWinners)->get();

            foreach ($users as $user) {
                $usersMap[$user->display_name] = $user;
            }
        }

        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'><th class='w-[50%] xl:w-[60%]'>User</th><th>Mode</th><th class='text-right'>Unlocked</th></tr>";
        $iter = 0;

        foreach ($unlocks as $userObject) {
            $userWinner = $userObject['User'];
            if ($userWinner == null || $userObject['DateAwarded'] == null || !isset($usersMap[$userWinner])) {
                continue;
            }

            $niceDateWon = date("d M, Y H:i", strtotime($userObject['DateAwarded']));
            echo "<tr>";
            echo "<td class='w-[32px]'>";
            echo userAvatar($usersMap[$userWinner], iconClass: 'mr-2');
            echo "</td>";
            echo "<td>";
            if ($userObject['HardcoreMode']) {
                echo "Hardcore";
            }
            echo "</td>";
            echo "<td class='text-right'>$niceDateWon</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    }
    ?>
    </div>
    @if (!$isEventGame)
    <x-slot name="sidebar">
        <x-game.leaderboards-listing :game="$game" />
    </x-slot>
    @endif
</x-app-layout>
