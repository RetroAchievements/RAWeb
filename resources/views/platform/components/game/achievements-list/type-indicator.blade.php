@props([
    'achievementType' => null,
    'beatenGameCreditDialogContext' => 's:|h:',
    'gameId' => null,
    'isCreditDialogEnabled' => true,
])

<?php
use App\Platform\Enums\AchievementType;
use Illuminate\Http\Request;

$gameId ??= request()->route('game');

$progressionTypeValue = AchievementType::Progression;
$winConditionTypeValue = AchievementType::WinCondition;
$missableTypeValue = AchievementType::Missable;

$backgroundColorClassName = "bg-embed light:bg-neutral-50";
if ($achievementType === $missableTypeValue) {
    $backgroundColorClassName = "bg-amber-950 light:bg-amber-100";
}
?>

<x-modal-trigger
    modalTitleLabel="Beaten Game Credit"
    resourceApiRoute="/request/game/beaten-credit.php"
    :resourceId="$gameId"
    :resourceContext="$beatenGameCreditDialogContext"
    :disabled="!$isCreditDialogEnabled || $achievementType === $missableTypeValue"
>
    <x-slot name="trigger">
        <div class="flex items-center {{ $backgroundColorClassName }} group light:border light:border-neutral-300 p-1 rounded-full text-neutral-200 light:text-neutral-500 overflow-hidden">
            @if ($achievementType === $progressionTypeValue)
                <span class="
                    text-[0.6rem] transition translate-x-4 duration-300 ease-out w-0 opacity-0 invisible 
                    group-hover:md:visible group-hover:md:w-[60px] group-hover:md:opacity-100
                    group-hover:md:ml-1 group-hover:md:mr-2 group-hover:md:translate-x-0
                    select-none font-semibold
                ">
                    Progression
                </span>
                <div class="w-[18px] h-[18px]" aria-label="Progression">
                    <x-icon.progression />
                </div>
            @endif

            @if ($achievementType === $winConditionTypeValue)
                <span class="
                    text-[0.6rem] transition translate-x-3 duration-300 ease-out w-0 opacity-0 invisible 
                    group-hover:md:visible group-hover:md:w-[60px] group-hover:md:opacity-100 
                    group-hover:md:ml-1 group-hover:md:mr-[18px] group-hover:md:translate-x-0
                    select-none whitespace-nowrap font-semibold
                ">
                    Win Condition
                </span>
                <div class="w-[18px] h-[18px]" aria-label="Win Condition">
                    <x-icon.win-condition />
                </div>
            @endif

            @if ($achievementType === $missableTypeValue)
                <span class="
                    text-[0.6rem] transition translate-x-4 duration-300 ease-out w-0 opacity-0 invisible 
                    group-hover:md:visible group-hover:md:w-[44px] group-hover:md:opacity-100
                    group-hover:md:ml-1 group-hover:md:mr-2 group-hover:md:translate-x-0
                    select-none font-semibold z-10
                ">
                    Missable
                </span>
                <div class="w-[18px] h-[18px] z-10" aria-label="Progression">
                    <x-icon.missable />
                </div>

                {{-- Backwards compatibility for users who search the page by "[m]" --}}
                <span class="absolute text-amber-950 light:text-amber-100 text-2xs">[m]</span>
            @endif
        </div>
    </x-slot>
</x-modal-trigger>
