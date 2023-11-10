@props([
    'recentLeaderboardEntries' => null, // Collection
])

<div>
    <h2 class="text-h4">Recent Leaderboard Entries</h2>

    <div class="h-[500px] max-h-[500px] overflow-y-auto border border-embed-highlight bg-embed rounded">
        <table class="table-highlight w-full">
            <thead class="sticky top-0 z-10 w-full bg-embed">
                <tr class="do-not-highlight">
                    <th>Game</th>
                    <th>Leaderboard</th>
                    <th>User</th>
                    <th>Entry</th>
                    <th>Submitted</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($recentLeaderboardEntries as $recentLeaderboardEntry)
                    <tr>
                        <td class="py-2">
                            <x-game.multiline-avatar
                                :gameId="$recentLeaderboardEntry->GameID"
                                :gameTitle="$recentLeaderboardEntry->GameTitle"
                                :gameImageIcon="$recentLeaderboardEntry->GameIcon"
                                :consoleName="$recentLeaderboardEntry->ConsoleName"
                            />
                        </td>

                        <td>
                            <a href="/leaderboardinfo.php?i={{ $recentLeaderboardEntry->LeaderboardID }}">
                            {{ $recentLeaderboardEntry->Title }}
                            </a>
                        </td>

                        <td>
                            {!! userAvatar($recentLeaderboardEntry->User) !!}
                        </td>

                        <td>{{ GetFormattedLeaderboardEntry($recentLeaderboardEntry->Format, $recentLeaderboardEntry->Score) }}</td>

                        <td>{{ $recentLeaderboardEntry->TimestampLabel }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
