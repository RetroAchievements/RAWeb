@props([
    'recentUnlocks' => null, // Collection
])

<div>
    <h2 class="text-h4">Recent Unlocks</h2>

    <div class="h-[500px] max-h-[500px] overflow-y-auto border border-embed-highlight bg-embed rounded">
        @if ($recentUnlocks->isEmpty())
            <x-developer-feed.table-empty-state>
                Couldn't find any recent unlocks.
            </x-developer-feed.table-empty-state>
        @else
            <table class="table-highlight w-full">
                <thead class="sticky top-0 z-10 w-full bg-embed">
                    <tr class="do-not-highlight">
                        <th>Achievement</th>
                        <th>Game</th>
                        <th>User</th>
                        <th>Unlocked</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($recentUnlocks as $recentUnlock)
                        <tr>
                            <td class="py-2">
                                {!!
                                    achievementAvatar([
                                        'ID' => $recentUnlock->achievement_id,
                                        'Title' => $recentUnlock->Title,
                                        'Points' => $recentUnlock->Points,
                                        'BadgeName' => $recentUnlock->BadgeName,
                                        'HardcoreMode' => !!$recentUnlock->unlocked_hardcore_at,
                                    ])
                                !!}

                                @if (!$recentUnlock->unlocked_hardcore_at)
                                    <span class="text-xs text-text-muted">(softcore)</span>
                                @endif
                            </td>

                            <td>
                                <x-game.multiline-avatar
                                    :gameId="$recentUnlock->GameID"
                                    :gameTitle="$recentUnlock->GameTitle"
                                    :gameImageIcon="$recentUnlock->GameIcon"
                                    :consoleName="$recentUnlock->ConsoleName"
                                />
                            </td>

                            <td>
                                {!! userAvatar($recentUnlock->User) !!}
                            </td>

                            <td>{{ $recentUnlock->TimestampLabel }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
