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
                        {{ $totalPoints > 0 ? localized_number($totalPoints) : '-' }}
                    </span>
                    points
                </p>

                <x-retropoints-container>
                    <p class="text-2xs whitespace-nowrap {{ $totalRetroPoints === 0 ? 'text-text-muted' : '' }}">
                        @if ($totalRetroPoints > 0)
                            ({{ localized_number($totalRetroPoints) }})
                        @else
                            -
                        @endif
                        RP
                    </p>
                </x-retropoints-container>
            </div>
        </td>
    @endif
</tr>
