@props([
    'consoles' => [],
    'games' => [],
    'sortOrder' => 'title',
    'filterOptions' => [],
    'userProgress' => null,
])

<div>
    @if (count($consoles) < 1)
        <p>No related games.</p><br/>
    @else
        <x-game.related-games-meta-panel
            :selectedSortOrder="$sortOrder"
            :filterOptions="$filterOptions"
        />

        @if ($userProgress !== null)
        <?php
            $addButtonTooltip = __('user-game-list.play.add');
            $removeButtonTooltip = __('user-game-list.play.remove');
        ?>
        <script>
            function togglePlayListItem(id)
            {
                $.post('/request/user-game-list/toggle.php', {
                    type: 'play',
                    game: id
                })
                .done(function () {
                    $("#add-to-list-" + id).toggle();
                    $("#remove-from-list-" + id).toggle();
                    if ($("#add-to-list-" + id).is(':visible')) {
                        $("#play-list-button-" + id).prop('title', '{{ $addButtonTooltip }}');
                    } else {
                        $("#play-list-button-" + id).prop('title', '{{ $removeButtonTooltip }}');
                    }
                });
            }
        </script>
        @endif

        @foreach ($consoles as $console)
            @if ($filterOptions['console'])
                <h2 class="flex gap-x-2 items-center text-h3">
                    <img src="{{ getSystemIconUrl($console->ID) }}" alt="Console icon" width="24" height="24">
                    <span>{{ $console->Name }}</span>
                </h2>
            @endif

            <div><table class='table-highlight mb-4'><tbody>

            <tr>
                <th style='width:28%'>Title</th>
                <th style='width:12%; cursor: help' class='text-right'
                    title='The number of achievements in the set'>Achievements</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='The number of points associated to achievements in the set'>Points</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='An estimate of rarity for achievements in the set'>RetroRatio</th>
                <th style='width:10%; cursor: help' class='text-right'
                    title='The number of leaderboards in the set'>Leaderboards</th>
                <th style='width:8%; cursor: help' class='text-right'
                    title='The number of users who have played the set'>Players</th>
                <th style='width:8%; cursor: help' class='text-right'
                    title='The number of open tickets for achievements in the set'>Tickets</th>
                @if ($userProgress !== null)
                    <th style='width:8%; cursor: help' class='text-center'
                        title='Indicates how close you are to mastering a set'>Progress</th>
                    <th style='width:6%; cursor: help' class='text-center'
                        title='Whether or not the game is on your want to play list'>Backlog</th>
                @endif
            </tr>
            <?php $count = $achievementCount = $pointCount = $leaderboardCount = $ticketCount = 0; ?>
            @foreach ($games as $game)
                @if ($filterOptions['console'] && $game['ConsoleID'] != $console['ID'])
                    @continue
                @endif
                <tr>
                    @if (!$filterOptions['console'])
                    <td class="py-2">
                        <x-game.multiline-avatar
                            :gameId="$game['ID']"
                            :gameTitle="$game['Title']"
                            :gameImageIcon="$game['ImageIcon']"
                            :consoleName="$game['ConsoleName']"
                        />
                    @else
                    <td>
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
                                $gameProgress = $userProgress[$game['ID']] ?? null;
                                $softcoreProgress = $gameProgress['achievements_unlocked'] ?? 0;
                                $hardcoreProgress = $gameProgress['achievements_unlocked'] ?? 0;
                                $tooltip = "$softcoreProgress of {$game['achievements_published']} unlocked";
                            ?>
                            <x-hardcore-progress
                                :softcoreProgress="$softcoreProgress"
                                :hardcoreProgress="$hardcoreProgress"
                                :maxProgress="$game['achievements_published']"
                                :tooltip="$tooltip"
                            />
                            </td>
                        @endif

                        <td class='text-center'>
                        <?php
                            $addVisibility = '';
                            $removeVisibility = '';
                            if ($game['WantToPlay'] ?? false) {
                                $addVisibility = 'hidden';
                                $buttonTooltip = $removeButtonTooltip;
                            } else {
                                $removeVisibility = 'hidden';
                                $buttonTooltip = $addButtonTooltip;
                            }
                        ?>
                        <button id="play-list-button-{{ $game['ID'] }}" class="btn" type="button" title="{{ $buttonTooltip }}"
                                onClick="togglePlayListItem({{ $game['ID'] }})">
                            <div class="flex items-center gap-x-1">
                                <div id="add-to-list-{{ $game['ID'] }}" class="{{ $addVisibility }}">
                                    <x-fas-plus class="-mt-0.5 w-[12px] h-[12px]" />
                                </div>
                                <div id="remove-from-list-{{ $game['ID'] }}" class="{{ $removeVisibility }}">
                                    <x-fas-check class="-mt-0.5 w-[12px] h-[12px]" />
                                </div>
                            </div>
                        </button>
                        </td>
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
