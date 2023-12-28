@props([
    'type' => null, // AchievementType
])

<?php
use App\Platform\Enums\AchievementType;
use App\Site\Enums\UserPreference;
use Illuminate\Support\Facades\Auth;

$progressionType = AchievementType::Progression;
$winConditionType = AchievementType::WinCondition;
$missableType = AchievementType::Missable;

$canShowTypeIndicator = true;
if ($type === $missableType) {
    $currentUser = Auth::user();
    if (isset($currentUser) && BitSet($currentUser->websitePrefs, UserPreference::Game_HideMissableIndicators)) {
        $canShowTypeIndicator = false;
    }
}
?>

@if ($type && $canShowTypeIndicator)
    <div class="h-[24px] max-h-[24px] flex items-center rounded-full bg-embed border pl-1 pr-2 py-0.5 max-w-fit whitespace-nowrap {{ $type === $missableType ? 'border-dashed border-neutral-600 gap-0.5' : 'border-embed-highlight gap-1' }}">
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