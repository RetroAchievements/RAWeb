@props([
    'user' => null,
    'consoles' => [],
    'games' => [],
    'sortOrder' => 'console',
    'soleDeveloper' => false,
    'userProgress' => null,
])

<?php
$makeLink = function($text, $value) use ($sortOrder, $soleDeveloper) {
    if ($value == '') {
        // sole developer toggle
        $suffix = $soleDeveloper ? '' : '&sole=1';
        return "<a href='?sort=$sortOrder$suffix'>$text</a>";
    }

    $suffix = $soleDeveloper ? '&sole=1' : '';

    if ($sortOrder === $value) {
        return "<a href='?sort=-$value$suffix'>$text &#x25B2;</a>";
    }

    if ($sortOrder == "-$value") {
        return "<a href='?sort=$value$suffix'>$text &#x25BC;</a>";
    }

    return "<a href='?sort=$value$suffix'>$text</a>";
};
?>

<x-app-layout
    pageTitle="{{ $user->User }} - Developed Games"
    pageDescription="Games developed by {{ $user->User }}"
>
    <div class='navpath'>
        {!! renderUserBreadcrumb($user) !!}
        &raquo; <b>Developed Games</b>
    </div>

    @if (count($consoles) < 1)
        <p>No developed games.</p>
    @else
        <p class='mb-2'>
        @if ($sortOrder === 'console')
            Sort by:
                {!! $makeLink('Title', 'title') !!},
                {!! $makeLink('Achievements', 'achievements') !!},
                {!! $makeLink('Points', 'points') !!},
                {!! $makeLink('RetroRatio', 'retroratio') !!},
                {!! $makeLink('Leaderboards', 'leaderboards') !!},
                {!! $makeLink('Players', 'players') !!},
                {!! $makeLink('Tickets', 'tickets') !!},
                {!! $makeLink('Progress', 'progress') !!}
        @else
            {!! $makeLink('Sort by console', 'console') !!}
        @endif

        @if ($soleDeveloper)
            | {!! $makeLink('Any development', '') !!}
        @else
            | {!! $makeLink('Sole development', '') !!}
        @endif
        </p>

        @foreach ($consoles as $console)
            @if ($sortOrder === 'console')
                <h2 class="flex gap-x-2 items-center text-h3">
                    <img src="{{ getSystemIconUrl($console->ID) }}" alt="Console icon" width="24" height="24">
                    <span>{{ $console->Name }}</span>
                </h2>
                <?php $makeLink = function($text, $value) { return $text; }; ?>
            @endif

            <div><table class='table-highlight mb-4'><tbody>

            <tr>
                <th style='width:34%'>{!! $makeLink('Title', 'title') !!}</th>
                <th style='width:12%' class='text-right'>{!! $makeLink('Achievements', 'achievements') !!}</th>
                <th style='width:10%' class='text-right'>{!! $makeLink('Points', 'points') !!}</th>
                <th style='width:10%' class='text-right'>{!! $makeLink('RetroRatio', 'retroratio') !!}</th>
                <th style='width:10%' class='text-right'>{!! $makeLink('Leaderboards', 'leaderboards') !!}</th>
                <th style='width:8%' class='text-right'>{!! $makeLink('Players', 'players') !!}</th>
                <th style='width:8%' class='text-right'>{!! $makeLink('Tickets', 'tickets') !!}</th>
                @if ($userProgress !== null)
                    <th style='width:8%' class='text-right'>{!! $makeLink('Progress', 'progress') !!}</th>
                @endif
            </tr>
            <?php $count = $achievementCount = $pointCount = $leaderboardCount = $ticketCount = 0; ?>
            @foreach ($games as $game)
                @if ($sortOrder !== 'console' || $game['ConsoleID'] == $console['ID'])
                    <?php
                        $count++;
                        $achievementCount += $game['NumAuthoredAchievements'];
                        $pointCount += $game['NumAuthoredPoints'];
                        $leaderboardCount += $game['NumAuthoredLeaderboards'];
                        $ticketCount += $game['NumAuthoredTickets'];
                    ?>
                    <tr>
                        <td>
                        @if ($sortOrder !== 'console')
                            <x-game.multiline-avatar
                                :gameId="$game['ID']"
                                :gameTitle="$game['Title']"
                                :gameImageIcon="$game['ImageIcon']"
                                :consoleName="$game['ConsoleName']"
                            />
                        @else
                            <x-game.multiline-avatar
                                :gameId="$game['ID']"
                                :gameTitle="$game['Title']"
                                :gameImageIcon="$game['ImageIcon']"
                            />
                        @endif
                        </td>

                        @if ($game['NumAuthoredAchievements'] == $game['achievements_published'])
                            <td class='text-right'>{{ $game['NumAuthoredAchievements'] }}</td>
                            <td class='text-right'>{{ $game['NumAuthoredPoints'] }}</td>
                        @else
                            <td class='text-right'>{{ $game['NumAuthoredAchievements'] }} of {{ $game['achievements_published'] }}</td>
                            <td class='text-right'>{{ $game['NumAuthoredPoints'] }} of {{ $game['points_total'] }}</td>
                        @endif

                        <td class='text-right'>{!! sprintf("%01.2f", $game['RetroRatio']) !!}</td>

                        @if ($game['leaderboards_count'] == 0)
                            <td></td>
                        @elseif ($game['NumAuthoredLeaderboards'] == $game['leaderboards_count'])
                            <td class='text-right'>{{ $game['NumAuthoredLeaderboards'] }}</td>
                        @else
                            <td class='text-right'>{{ $game['NumAuthoredLeaderboards'] }} of {{ $game['leaderboards_count'] }}</td>
                        @endif

                        <td class='text-right'>{{ $game['players_total'] }}</td>

                        @if ($game['NumTickets'] == 0)
                            <td></td>
                        @elseif ($game['NumAuthoredTickets'] == $game['NumTickets'])
                            <td class='text-right'><a href="/ticketmanager.php?g={{ $game['ID'] }}">{{ $game['NumAuthoredTickets'] }}</a></td>
                        @else
                            <td class='text-right'><a href="/ticketmanager.php?g={{ $game['ID'] }}">{{ $game['NumAuthoredTickets'] }} of {{ $game['NumTickets'] }}</a></td>
                        @endif

                        @if ($userProgress !== null)
                            <td>
                            <?php
                                $hardcoreProgressBarWidth = $softcoreProgressBarWidth = 0;
                                $gameProgress = $userProgress[$game['ID']] ?? null;
                                if ($gameProgress != null) {
                                    $hardcoreProgressBarWidth = sprintf("%01.2f", $gameProgress['achievements_unlocked_hardcore'] * 100 / $game['achievements_published']);
                                    $softcoreProgressBarWidth = sprintf("%01.2f", ($gameProgress['achievements_unlocked'] - $gameProgress['achievements_unlocked_hardcore']) * 100 / $game['achievements_published']);
                                }
                            ?>
                            <div role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                 class="w-full h-1 bg-embed rounded flex">
                                <div style="width: {{ $hardcoreProgressBarWidth }}%"
                                     class="bg-[#cc9900] h-full {{ $hardcoreProgressBarWidth > 0 ? 'rounded-l' : '' }}"></div>
                                <div style="width: {{ $softcoreProgressBarWidth }}%"
                                     class="bg-[rgb(11,113,193)] h-full {{ $hardcoreProgressBarWidth === 0 ? 'rounded-l' : '' }}"></div>
                            </div>
                            </td>
                        @endif
                    </tr>
                @endif
            @endforeach
            @if ($count > 1)
                <tr>
                    <td><b>Total:</b> {{ $count }} games</td>
                    <td class='text-right'><b>{!! localized_number($achievementCount) !!}</b></td>
                    <td class='text-right'><b>{!! localized_number($pointCount) !!}</b></td>
                    <td></td>
                    <td class='text-right'><b>{!! localized_number($leaderboardCount) !!}</b></td>
                    <td></td>
                    <td class='text-right'><b>{!! localized_number($ticketCount) !!}</b></td>
                    @if ($userProgress !== null)
                        <td></td>
                    @endif
                </tr>
            @endif

            </tbody></table></div>

            @if ($sortOrder != 'console')
                @break
            @endif
        @endforeach
    @endif

</x-app-layout>
