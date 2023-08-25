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

    @if ($isFullyFeaturedGame && $totalPoints > 0)
        <td>
            <div class="flex flex-col items-end">
                <p class="text-2xs whitespace-nowrap {{ $totalPoints === 0 ? 'text-text-muted' : '' }}">
                    <span>
                        {{ localized_number($totalPoints) }} points
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
        <td class="min-w-[86px]"></td>
    @endif
</tr>
