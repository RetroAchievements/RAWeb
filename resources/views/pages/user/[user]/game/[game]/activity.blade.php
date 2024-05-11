<?php

use App\Enums\PlayerGameActivityEventType;
use App\Enums\PlayerGameActivitySessionType;
use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\PlayerGameActivityPageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,game']);
name('user.game.activity');

render(function (View $view, User $user, Game $game, PlayerGameActivityPageService $pageService) {
    if (!Auth::user()->can('viewSessionHistory', App\Models\PlayerGame::class)) {
        abort(401);
    }

    return $view->with($pageService->buildViewData($user, $game));
});

?>

<x-app-layout pageTitle="{{ $user->User }}'s activity for {{ $game->Title }}">
    <x-user.breadcrumbs
        :targetUsername="$user->User"
        :parentPage="$game->Title"
        :parentPageUrl="$game->permalink"
        currentPage="Activity"
    />

    <div class="mt-3 w-full relative flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Game Activity: {{ $user->User }}</h1>
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
    <p>{{ $user->User }} has not played {{ $game->Title }}.</p>
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
                        @if ($session['type'] === PlayerGameActivitySessionType::Player)
                            <td>
                                <span class='text-muted'>Started Playing</span>
                                @if ($session['userAgent'])
                                    <span class="smalltext" title="{{ $session['userAgent'] }}">
                                    @php $userAgent = $userAgentService->decode($session['userAgent']) @endphp
                                    {{ $userAgent['client'] }}
                                    @if ($userAgent['clientVersion'] !== 'Unknown')
                                        {{ $userAgent['clientVersion'] }}
                                    @endif
                                    @if (array_key_exists('clientVariation', $userAgent))
                                        - {{ $userAgent['clientVariation'] }}
                                    @endif
                                    @if (!empty($userAgent['os']))
                                        ({{ $userAgent['os'] }})
                                    @endif
                                    </span>
                                @endif
                            </td>
                        @elseif ($session['type'] === PlayerGameActivitySessionType::Generated)
                            <td class='text-muted'>Generated Session</td>
                        @elseif ($session['type'] === PlayerGameActivitySessionType::ManualUnlock)
                            <td class='text-muted'>Manual Unlock</td>
                        @else
                            <td class='text-muted'>{{ $session['type'] }}</td>
                        @endif
                    </tr>

                    @php $prevWhen = $session['startTime'] @endphp
                    @foreach ($session['events'] as $event)
                        <tr>
                            <td>
                                <span>&nbsp;</span>
                                <span>{{ $event['when']->format("H:i:s") }}</span>
                                <span class='smalltext text-muted'> (+{{ formatHms($event['when']->diffInSeconds($prevWhen)) }})</span>
                            </td>
                            <td>
                                @if ($event['type'] === PlayerGameActivityEventType::Unlock)
                                    @php $achievement = $event['achievement'] @endphp
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
                                @elseif ($event['type'] === PlayerGameActivityEventType::RichPresence)
                                    <span class='text-muted'>Rich Presence:</span>
                                    <span>{{ $event['description'] }}</span>
                                @endif
                            </td>
                        </tr>
                        @php $prevWhen = $event['when'] @endphp
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
