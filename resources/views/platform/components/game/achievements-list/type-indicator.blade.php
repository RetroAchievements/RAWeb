@props([
    'achievementType' => null,
    'beatenGameCreditDialogContext' => 's:|h:',
    'gameId' => null,
    'progressionTypeValue' => 'progression', // `AchievementType`
    'winConditionTypeValue' => 'win_condition', // `AchievementType`

    // If true, always show the type as Progression, even if it's something else.
    'useProgressionMask' => false,
])

<?php
use Illuminate\Http\Request;

$gameId ??= request()->route('game');

if ($achievementType === $winConditionTypeValue && $useProgressionMask) {
    $achievementType = $progressionTypeValue;
}
?>

@if ($achievementType === $progressionTypeValue || $achievementType === $winConditionTypeValue)
    <x-modal-trigger
        modalTitleLabel="Beaten Game Credit"
        resourceApiRoute="/request/game/beaten-credit.php"
        resourceId="{{ $gameId }}"
        :resourceContext="$beatenGameCreditDialogContext"
    >
        <x-slot name="trigger">
            <div class="flex items-center group bg-embed light:bg-neutral-50 light:border light:border-neutral-300 p-1 rounded-full text-neutral-200 light:text-neutral-500">
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
            </div>
        </x-slot>
    </x-modal-trigger>
@endif
