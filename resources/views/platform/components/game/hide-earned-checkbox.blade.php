<div x-data="toggleAchievementRowsComponent()">
    <label class="flex items-center gap-x-1 select-none">
        <input 
            type="checkbox"
            autocomplete="off"
            @change="toggleUnlockedRows"
        >
            Hide unlocked achievements
        </input>
    </label>
</div>
