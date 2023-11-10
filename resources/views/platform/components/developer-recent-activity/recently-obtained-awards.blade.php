@props([
    'recentAwards' => null, // Collection
])

<div>
    <h2 class="text-h4">Recent Awards</h2>

    <div class="h-[500px] max-h-[500px] overflow-y-auto border border-embed-highlight bg-embed rounded">
        <table class="table-highlight w-full">
            <thead class="sticky top-0 z-10 w-full bg-embed">
                <tr class="do-not-highlight">
                    <th>Game</th>
                    <th>User</th>
                    <th>Award</th>
                    <th>Earned</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($recentAwards as $recentAward)
                    <tr>
                        <td class="py-2">
                            <x-game.multiline-avatar
                                :gameId="$recentAward->AwardData"
                                :gameTitle="$recentAward->GameTitle"
                                :gameImageIcon="$recentAward->GameIcon"
                                :consoleName="$recentAward->ConsoleName"
                            />
                        </td>

                        <td>
                            {!! userAvatar($recentAward->User) !!}
                        </td>

                        <td>{{ $recentAward->AwardKindLabel }}</td>

                        <td>{{ $recentAward->TimestampLabel }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
