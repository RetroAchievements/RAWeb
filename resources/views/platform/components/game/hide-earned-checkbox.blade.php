<div x-data="hideEarnedCheckboxComponent()">
    <label class="flex items-center gap-x-1">
        <input 
            type="checkbox"
            autocomplete="off"
            @change="toggleUnlockedRows"
        >
            Hide unlocked achievements
        </input>
    </label>
</div>