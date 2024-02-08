@props([
    'hardcorePoints' => null, // ?int
    'softcorePoints' => null, // ?int
    'weightedPoints' => null, // ?int
])

<?php
$progressMode = 'hardcore';
if ($softcorePoints > $hardcorePoints) {
    $progressMode = 'softcore';
}
?>

<p>
    <span class="font-bold">
        @if ($progressMode === 'softcore')
            Softcore
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

        @if ($progressMode === 'softcore')
            {{ localized_number($softcorePoints) }}
        @endif
    </span>    
</p>
