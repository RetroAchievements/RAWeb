@props([
    'recentLeaderboardEntries' => null, // Collection
])

<?php

use App\Platform\Enums\ValueFormat;

?>

<div>
    <h2 class="text-h4">Recent Leaderboard Entries</h2>

    <div class="h-[500px] max-h-[500px] overflow-y-auto border border-embed-highlight bg-embed rounded">
        @if ($recentLeaderboardEntries->isEmpty())
            <x-empty-state variant="no-background">
                Couldn't find any recent leaderboard entries.
            </x-empty-state>
        @else
            <table class="table-highlight w-full">
                <thead class="sticky top-0 z-10 w-full bg-embed">
                    <tr class="do-not-highlight">
                        <th>Leaderboard</th>
                        <th>Entry</th>
                        <th>Game</th>
                        <th>User</th>
                        <th>Submitted</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($recentLeaderboardEntries as $recentLeaderboardEntry)
                        <tr>
                            <td>
                                <a href="/leaderboardinfo.php?i={{ $recentLeaderboardEntry->LeaderboardID }}">
                                {{ $recentLeaderboardEntry->Title }}
                                </a>
                            </td>

                            <td>{{ ValueFormat::format($recentLeaderboardEntry->Score, $recentLeaderboardEntry->Format) }}</td>

                            <td class="py-2">
                                <x-game.multiline-avatar
                                    :gameId="$recentLeaderboardEntry->GameID"
                                    :gameTitle="$recentLeaderboardEntry->GameTitle"
                                    :gameImageIcon="$recentLeaderboardEntry->GameIcon"
                                    :consoleName="$recentLeaderboardEntry->ConsoleName"
                                />
                            </td>

                            <td>
                                {!! userAvatar($recentLeaderboardEntry->User) !!}
                            </td>

                            <td class="smalldate">{{ $recentLeaderboardEntry->TimestampLabel }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
