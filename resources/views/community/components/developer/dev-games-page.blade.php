@props([
    'user' => null,
    'consoles' => [],
    'games' => [],
])

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
        @foreach ($consoles as $console)
            <h2 class="flex gap-x-2 items-center text-h3">
                <img src="{{ getSystemIconUrl($console->ID) }}" alt="Console icon" width="24" height="24">
                <span>{{ $console->Name }}</span>
            </h2>
            <div><table class='table-highlight mb-4'><tbody>

            <tr>
                <th style='width:68%'>Title</th>
                <th style='width:8%' class='text-right'>Achievements</th>
                <th style='width:8%' class='text-right'>Points</th>
                <th style='width:8%' class='text-right'>Leaderboards</th>
                <th style='width:8%' class='text-right'>Players</th>
            </tr>
            <?php $count = 0; $achievementCount = 0; $pointCount = 0; $leaderboardCount = 0; ?>
            @foreach ($games as $game)
                @if ($game['ConsoleID'] == $console->ID)
                    <?php
                        $count++;
                        $achievementCount += $game['NumAuthoredAchievements'];
                        $pointCount += $game['NumAuthoredPoints'];
                        $leaderboardCount += $game['NumAuthoredLeaderboards'];
                    ?>
                    <tr>
                        <td>
                        <x-game.multiline-avatar
                            :gameId="$game['ID']"
                            :gameTitle="$game['Title']"
                            :gameImageIcon="$game['ImageIcon']"
                        />
                        </td>

                        @if ($game['NumAuthoredAchievements'] == $game['achievements_published'])
                            <td class='text-right'>{{ $game['NumAuthoredAchievements'] }}</td>
                            <td class='text-right'>{{ $game['NumAuthoredPoints'] }}</td>
                        @else
                            <td class='text-right'>{{ $game['NumAuthoredAchievements'] }} of {{ $game['achievements_published'] }}</td>
                            <td class='text-right'>{{ $game['NumAuthoredPoints'] }} of {{ $game['points_total'] }}</td>
                        @endif

                        @if ($game['leaderboards_count'] == 0)
                            <td></td>
                        @elseif ($game['NumAuthoredLeaderboards'] == $game['leaderboards_count'])
                            <td class='text-right'>{{ $game['NumAuthoredLeaderboards'] }}</td>
                        @else
                            <td class='text-right'>{{ $game['NumAuthoredLeaderboards'] }} of {{ $game['leaderboards_count'] }}</td>
                        @endif

                        <td class='text-right'>{{ $game['players_total'] }}</td>
                    </tr>
                @endif
            @endforeach
            @if ($count > 1)
                <tr>
                    <td><b>Total:</b> {{ $count }} games</td>
                    <td class='text-right'><b>{{ $achievementCount }}</b></td>
                    <td class='text-right'><b>{{ $pointCount }}</b></td>
                    <td class='text-right'><b>{{ $leaderboardCount }}</b></td>
                    <td></td>
                </tr>
            @endif

            </tbody></table></div>
        @endforeach
    @endif

</x-app-layout>
