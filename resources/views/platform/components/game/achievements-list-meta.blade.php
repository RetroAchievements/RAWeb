@props([
    'isOfficial' => true,
    'numAchievements' => 0,
    'numMissableAchievements' => 0,
    'totalPossible' => 0, // total possible points
    'totalPossibleTrueRatio' => 0, // total possible RetroPoints
])

<div x-data="toggleAchievementRowsComponent()" class="flex flex-col gap-y-1">
    <p>
        There {{ $numAchievements === 1 ? 'is' : 'are'}}
        <span class="font-bold">
            {{ localized_number($numAchievements) }}
            @if (!$isOfficial)
                Unofficial
            @endif
        </span>
        {{ mb_strtolower(__res('achievement', $numAchievements)) }}
        worth
        <span class="font-bold">
            {{ localized_number($totalPossible) }}
        </span>
        <x-points-weighted-container>({{ localized_number($totalPossibleTrueRatio )}})</x-points-weighted-container>
        {{ mb_strtolower(__res('point', $totalPossible)) }}.
    </p>

    @if ($numMissableAchievements > 0)
        <button class="flex items-center gap-x-1 hover:underline hover:text-link-hover text-link" @click="toggleNonMissableRows">
            <div class="w-5 h-5 p-0.5 rounded-full bg-embed border border-dashed border-stone-500 text-white">
                <x-icon.missable />
            </div>
            This set has {{ $numMissableAchievements }} missable {{ mb_strtolower(__res('achievement', $numMissableAchievements)) }}.
        </button>
    @endif
</div>
