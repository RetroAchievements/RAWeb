<?php

// TODO migrate to PlayerGameController::activity() pages/user/game/activity.blade.php

use App\Enums\Permissions;
use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\PlayerGameActivityService;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    abort(401);
}

$gameID = requestInputSanitized('ID', 0, 'integer');
$user2 = requestInputSanitized('f');

if (empty($user2) || $gameID <= 0) {
    abort(404);
}

$targetUser = User::firstWhere('User', $user2);
$game = Game::firstWhere('ID', $gameID);
if (!$targetUser || !$game) {
    abort(404);
}

$activity = new PlayerGameActivityService();
$activity->initialize($targetUser, $game);
$summary = $activity->summarize();

$estimated = ($summary['generatedSessionAdjustment'] !== 0) ? " (estimated)" : "";

$unlockSessionCount = $summary['achievementSessionCount'];
$sessionInfo = "$unlockSessionCount session";
if ($unlockSessionCount != 1) {
    $sessionInfo .= 's';

    if ($unlockSessionCount > 1) {
        $elapsedAchievementDays = ceil($summary['totalUnlockTime'] / (24 * 60 * 60));
        if ($elapsedAchievementDays > 2) {
            $sessionInfo .= " over $elapsedAchievementDays days";
        } else {
            $sessionInfo .= " over " . ceil($summary['totalUnlockTime'] / (60 * 60)) . " hours";
        }
    }
}

$gameAchievementCount = $game->achievements_published ?? 0;
$userProgress = ($gameAchievementCount > 0) ? sprintf("/%d (%01.2f%%)",
    $gameAchievementCount, $activity->achievementsUnlocked * 100 / $gameAchievementCount) : "n/a";
?>
<x-app-layout pageTitle="{{ $targetUser->User }}'s activity for {{ $game->Title }}">
    <x-user.breadcrumbs
        :targetUsername="$targetUser->User"
        :parentPage="$game->Title"
        :parentPageUrl="$game->permalink"
        currentPage="Activity"
    />

    <div class="mt-3 w-full relative flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Game Activity: {{ $targetUser->User }}</h1>
    </div>
@if (!empty($activity->sessions))
    <div>
    @if ($summary['totalPlaytime'] != $summary['achievementPlaytime'])
        <p>
            <span class="font-bold">Total Playtime:</span>
            <span>{{ formatHMS($summary['totalPlaytime']) }}{{ $estimated }}</span>
        </p>
    @endif
        <p>
            <span class="font-bold">Achievement Playtime:</span>
            <span>{{ formatHMS($summary['achievementPlaytime']) }}{{ $estimated }}</span>
        </p>
        <p>
            <span class="font-bold">Achievement Sessions:</span>
            <span>{{ $sessionInfo }}</span>
        </p>
        <p>
            <span class="font-bold">Achievements Unlocked:</span>
            <span>{{ $activity->achievementsUnlocked }}{{ $userProgress }}</span>
        </p>
    </div>
@endif

@if (empty($activity->sessions))
    <p>{{ $targetUser->User }} has not played {{ $game->Title }}.</p>
@else
    <div class="overflow-x-auto lg:overflow-x-visible">
        <table class="do-not-highlight mb-4">
            <thead>
                <tr class="do-not-highlight lg:sticky lg:top-[42px] z-[1] bg-box">
                    <th style="width:25%">When</th>
                    <th style="width:75%">What</th>
                </tr>
            </thead>
    
            <tbody>
                @foreach ($activity->sessions as $session)
                    <tr class='do-not-highlight'>
                        <td>{{ $session['startTime']->format("j M Y, H:i:s") }}</td>
                    @if ($session['type'] === 'player-session')
                        <td class='text-muted'>Started Playing</td>
                    @elseif ($session['type'] === 'generated')
                        <td class='text-muted'>Generated Session</td>
                    @elseif ($session['type'] === 'manual-unlock')
                        <td class='text-muted'>Manual Unlock</td>
                    @else
                        <td class='text-muted'>{{ $session['type'] }}</td>
                    @endif
                    </tr>

                    <?php $prevWhen = $session['startTime'] ?>
                    @foreach ($session['events'] as $event)
                        <tr>
                            <td>
                                <span>&nbsp;</span>
                                <span>{{ $event['when']->format("H:i:s") }}</span>
                                <span class='smalltext text-muted'> (+{{ formatHms($event['when']->diffInSeconds($prevWhen)) }})</span>
                            </td>
                            <td>
                                @if ($event['type'] === 'unlock')
                                    <?php $achievement = $event['achievement'] ?>
                                    {!! achievementAvatar($achievement) !!}
                                    @if ($achievement['Flags'] != AchievementFlag::OfficialCore)
                                        (Unofficial)
                                    @endif
                                    @if ($event['hardcoreLater'] ?? false)
                                        (unlocked later in hardcore)
                                    @endif
                                    @if ($event['unlocker'] ?? null)
                                        (unlocked by {!! userAvatar($event['unlocker'], label:true, icon:false) !!})
                                    @endif
                                @elseif ($event['type'] === 'rich-presence')
                                    <span class='text-muted'>Rich Presence:</span>
                                    <span>{{ $event['description'] }}</span>
                                @endif
                            </td>
                        </tr>
                        <?php $prevWhen = $event['when'] ?>
                    @endforeach
                    @if ($prevWhen != $session['endTime'])
                        <tr>
                            <td>
                                <span>&nbsp;</span>
                                <span>{{ $session['endTime']->format("H:i:s") }}</span>
                                <span class='smalltext text-muted'> (+{{ formatHms($session['endTime']->diffInSeconds($prevWhen)) }})</span>
                            </td>
                            <td class='text-muted'>End of session</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endif
</x-app-layout>
