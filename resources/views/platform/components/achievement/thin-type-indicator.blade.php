@props([
    'type' => null, // AchievementType
])

<?php
use App\Platform\Enums\AchievementType;

$progressionType = AchievementType::Progression;
$winConditionType = AchievementType::WinCondition;
$missableType = AchievementType::Missable;
?>

@if ($type)
    <div class="flex items-center gap-1 rounded-full bg-embed border-embed-highlight border px-2 py-0.5 max-w-fit whitespace-nowrap">
        <span class="w-[18px] h-[18px]">
            @if ($type === $progressionType)
                <x-icon.progression />
            @elseif ($type === $winConditionType)
                <x-icon.win-condition />
            @elseif ($type === $missableType)
                <x-icon.missable />
            @endif
        </span>

        <span class="text-2xs text-center">{{ __("achievement-type.{$type}") }}</span>
    </div>
@endif