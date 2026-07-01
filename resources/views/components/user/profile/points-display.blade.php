@props([
    'hardcorePoints' => null, // ?int
    'casualPoints' => null, // ?int
    'weightedPoints' => null, // ?int
])

<?php
$progressMode = 'hardcore';
if ($casualPoints > $hardcorePoints) {
    $progressMode = 'casual';
}
?>

<p>
    <span class="font-bold">
        @if ($progressMode === 'casual')
            Casual
        @endif

        Points:
    </span>

    <span>
        @if ($progressMode === 'hardcore')
            {{ localized_number($hardcorePoints) }}

            @if ($hardcorePoints > 0)
                <x-points-weighted-container>
                    ({{ localized_number($weightedPoints) }})
                </x-points-weighted-container>
            @endif
        @endif

        @if ($progressMode === 'casual')
            {{ localized_number($casualPoints) }}
        @endif
    </span>
</p>
