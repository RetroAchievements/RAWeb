@props([
    'user' => null,
    'consoles' => [],
    'games' => [],
    'sortOrder' => 'title',
    'filterOptions' => [],
    'userProgress' => null,
])

<x-app-layout
    pageTitle="{{ $user->User }} - Developed Sets"
    pageDescription="View achievement sets developed by {{ $user->User }} for various games on RetroAchievements"
>
    <x-user.breadcrumbs :targetUsername="$user->User" currentPage="Developed Sets" />

    <div class="mt-3 -mb-3 w-full flex gap-x-3">
        {!! userAvatar($user->User, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $user->User }}'s Developed Sets</h1>
    </div>

    <x-developer.sets-meta-panel
        :selectedSortOrder="$sortOrder"
        :filterOptions="$filterOptions"
    />

    @if (count($consoles) < 1)
        <p>No developed games.</p>
    @else
        @foreach ($consoles as $console)
            @if ($filterOptions['console'])
                <h2 class="flex gap-x-2 items-center text-h3">
                    <img src="{{ getSystemIconUrl($console->ID) }}" alt="Console icon" width="24" height="24">
                    <span>{{ $console->Name }}</span>
                </h2>
            @endif

            <div><table class='table-highlight mb-4'><tbody>

            <tr>
                <th style='width:34%'>Title</th>
                <th style='width:12%; cursor: help' class='text-right'
                    title='The number of achievements created by {{ $user->User }} in the set'>Achievements</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='The number of points associated to achievements created by {{ $user->User }} in the set'>Points</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='As estimate of rarity for achievements created by {{ $user->User }} in the set'>RetroRatio</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='The number of leaderboards created by {{ $user->User }} in the set'>Leaderboards</th>
                <th style='width:8%; cursor: help' class='text-right'
                    title='The number of users who have played the set'>Players</th>
                <th style='width:8%; cursor: help' class='text-right'
                    title='The number of open tickets for achievements created by {{ $user->User }} in the set'>Tickets</th>
                @if ($userProgress !== null)
                    <th style='width:8%; cursor: help' class='text-right'
                        title='Indicates how close you are to mastering a set'>Progress</th>
                @endif
            </tr>
            <?php $count = $achievementCount = $pointCount = $leaderboardCount = $ticketCount = 0; ?>
            @foreach ($games as $game)
                @if ($filterOptions['console'] && $game['ConsoleID'] != $console['ID'])
                    @continue
                @endif
                <?php
                    $count++;
                    $achievementCount += $game['NumAuthoredAchievements'];
                    $pointCount += $game['NumAuthoredPoints'];
                    $leaderboardCount += $game['NumAuthoredLeaderboards'];
                    $ticketCount += $game['NumAuthoredTickets'];
                ?>
                <tr>
                    <td>
                    @if (!$filterOptions['console'])
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
                        <td class='text-right'>{!! localized_number($game['NumAuthoredAchievements']) !!}</td>
                        <td class='text-right'>{!! localized_number($game['NumAuthoredPoints']) !!}</td>
                    @else
                        <td class='text-right'>{!! localized_number($game['NumAuthoredAchievements']) !!} of {!! localized_number($game['achievements_published']) !!}</td>
                        <td class='text-right'>{!! localized_number($game['NumAuthoredPoints']) !!} of {!! localized_number($game['points_total']) !!}</td>
                    @endif

                    <td class='text-right'>{!! sprintf("%01.2f", $game['RetroRatio']) !!}</td>

                    @if ($game['leaderboards_count'] == 0)
                        <td></td>
                    @elseif ($game['NumAuthoredLeaderboards'] == $game['leaderboards_count'])
                        <td class='text-right'>{!! localized_number($game['NumAuthoredLeaderboards']) !!}</td>
                    @else
                        <td class='text-right'>{!! localized_number($game['NumAuthoredLeaderboards']) !!} of {!! localized_number($game['leaderboards_count']) !!}</td>
                    @endif

                    <td class='text-right'>{!! localized_number($game['players_total']) !!}</td>

                    @if ($game['NumTickets'] == 0)
                        <td></td>
                    @elseif ($game['NumAuthoredTickets'] == $game['NumTickets'])
                        <td class='text-right'><a href="/ticketmanager.php?g={{ $game['ID'] }}">{!! localized_number($game['NumAuthoredTickets']) !!}</a></td>
                    @else
                        <td class='text-right'><a href="/ticketmanager.php?g={{ $game['ID'] }}">{!! localized_number($game['NumAuthoredTickets']) !!} of {!! localized_number($game['NumTickets']) !!}</a></td>
                    @endif

                    @if ($userProgress !== null)
                        <td>
                        <?php
                            $hardcoreProgressBarWidth = $softcoreProgressBarWidth = 0;
                            $gameProgress = $userProgress[$game['ID']] ?? null;
                            $achievementsUnlocked = 0;
                            if ($gameProgress != null && $game['achievements_published']) {
                                $achievementsUnlocked = $gameProgress['achievements_unlocked'];
                                $hardcoreProgressBarWidth = sprintf("%01.2f", $gameProgress['achievements_unlocked_hardcore'] * 100 / $game['achievements_published']);
                                $softcoreProgressBarWidth = sprintf("%01.2f", ($achievementsUnlocked - $gameProgress['achievements_unlocked_hardcore']) * 100 / $game['achievements_published']);
                            }
                        ?>
                        <div role="progressbar" aria-valuemin="0" aria-valuemax="100"
                             title="{{ $achievementsUnlocked }} of {{ $game['achievements_published'] }} unlocked"
                             class="w-full h-1 bg-embed rounded flex">
                            <div style="width: {{ $hardcoreProgressBarWidth }}%"
                                    class="bg-[#cc9900] h-full {{ $hardcoreProgressBarWidth > 0 ? 'rounded-l' : '' }}"></div>
                            <div style="width: {{ $softcoreProgressBarWidth }}%"
                                    class="bg-[rgb(11,113,193)] h-full {{ $hardcoreProgressBarWidth === 0 ? 'rounded-l' : '' }}"></div>
                        </div>
                        </td>
                    @endif
                </tr>
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

            @if (!$filterOptions['console'])
                @break
            @endif
        @endforeach
    @endif

</x-app-layout>
