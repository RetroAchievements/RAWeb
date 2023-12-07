@props([
    'canShowHideUnlockedAchievements' => false,
    'numMissableAchievements' => 0,
])

<div x-data="toggleAchievementRowsComponent()" class="flex gap-x-4 sm:flex-col md:flex-row lg:flex-col xl:flex-row">
    @if ($numMissableAchievements > 0)
        <label class="flex items-center gap-x-1 select-none transition lg:active:scale-95 cursor-pointer">
            <input
                type="checkbox"
                autocomplete="off"
                class="cursor-pointer"
                @change="toggleNonMissableRows"
            >
                Only show missables
            </input>
        </label>
    @endif

    @if ($canShowHideUnlockedAchievements)
        <label class="flex items-center gap-x-1 select-none transition lg:active:scale-95 cursor-pointer">
            <input 
                type="checkbox"
                autocomplete="off"
                class="cursor-pointer"
                @change="toggleUnlockedRows"
            >
                Hide unlocked achievements
            </input>
        </label>
    @endif
</div>
