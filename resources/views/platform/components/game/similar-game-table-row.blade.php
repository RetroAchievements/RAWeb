@props([
    'gameId' => 0,
    'gameTitle' => '',
    'gameImageIcon' => '',
    'consoleName' => '',
    'isFullyFeaturedGame' => false,
    'totalPoints' => 0,
    'totalRetroPoints' => 0,
])

<tr>
    <td class="w-full py-2">
        <x-game.multiline-avatar
            :gameId="$gameId"
            :gameTitle="$gameTitle"
            :gameImageIcon="$gameImageIcon"
            :consoleName="$consoleName"
        />
    </td>

    @if ($isFullyFeaturedGame)
        <td>
            <div class="flex flex-col items-end">
                <p class="text-2xs whitespace-nowrap {{ $totalPoints === 0 ? 'text-text-muted' : '' }}">
                    <span @if ($totalPoints > 0) class="font-semibold" @endif>
                        @if ($totalPoints > 0)
                            {{ localized_number($totalPoints) }} points
                        @else
                            <span title="This game doesn't have any achievements yet." class="cursor-help">
                                N/A
                            </span>
                        @endif
                    </span>
                </p>

                <x-points-weighted-container>
                    <p class="text-2xs whitespace-nowrap {{ $totalRetroPoints === 0 ? 'text-text-muted' : '' }}">
                        @if ($totalRetroPoints > 0)
                            ({{ localized_number($totalRetroPoints) }})
                        @endif
                    </p>
                </x-points-weighted-container>
            </div>
        </td>
    @else
        <td></td>
    @endif
</tr>
