@props([
    'totalPointsEarned' => 0,
])

<span wire:poll.visible.1500ms class="text-2xl" id="points-counter">
    {{ number_format($totalPointsEarned) }}
</span>
