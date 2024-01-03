@props([
    'consoles' => [],
    'games' => [],
    'sortOrder' => 'title',
    'filterOptions' => [],
    'userProgress' => null,
])

<div>
    <x-game.related-games-meta-panel
        :selectedSortOrder="$sortOrder"
        :filterOptions="$filterOptions"
    />

    @if (count($consoles) < 1)
        <p>No related games.</p>
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
                    title='The number of achievements in the set'>Achievements</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='The number of points associated to achievements in the set'>Points</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='As estimate of rarity for achievements in the set'>RetroRatio</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='The number of leaderboards in the set'>Leaderboards</th>
                <th style='width:8%; cursor: help' class='text-right'
                    title='The number of users who have played the set'>Players</th>
                <th style='width:8%; cursor: help' class='text-right'
                    title='The number of open tickets for achievements in the set'>Tickets</th>
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

                    @if ($game['achievements_published'] == 0)
                        <td></td>
                        <td></td>
                        <td></td>
                    @else
                        <td class='text-right'>{!! localized_number($game['achievements_published']) !!}</td>
                        <td class='text-right'>{!! localized_number($game['points_total']) !!}</td>
                        <td class='text-right'>{!! sprintf("%01.2f", $game['RetroRatio']) !!}</td>
                    @endif

                    @if ($game['leaderboards_count'] == 0)
                        <td></td>
                    @else
                        <td class='text-right'>{!! localized_number($game['leaderboards_count']) !!}</td>
                    @endif

                    @if ($game['players_total'] == 0)
                        <td></td>
                    @else
                        <td class='text-right'>{!! localized_number($game['players_total']) !!}</td>
                    @endif

                    @if ($game['NumTickets'] == 0)
                        <td></td>
                    @else
                        <td class='text-right'><a href="/ticketmanager.php?g={{ $game['ID'] }}">{!! localized_number($game['NumTickets']) !!}</a></td>
                    @endif

                    @if ($userProgress !== null)
                        @if ($game['achievements_published'] == 0)
                            <td></td>
                        @else
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
                    @endif
                </tr>
            @endforeach

            </tbody></table></div>

            @if (!$filterOptions['console'])
                @break
            @endif
        @endforeach
    @endif

</div>
