@props([
    'minAllowedDate' => '2014-08-22',
    'selectedAwardType' => null,
    'selectedDate' => null,
    'selectedUsers' => 'all',
])

<?php
use App\Community\Enums\AwardType;

$awardTypeMastery = AwardType::Mastery;
$awardTypeGameBeaten = AwardType::GameBeaten;
?>

<script>
/**
 * @param {FormEvent} event
 */
function handleDateSubmitted(event) {
    event.preventDefault();

    const inputEl = document.getElementById('filter-by-date');
    if (inputEl?.value) {
        window.updateUrlParameter('d', inputEl.value);
    }

    return false;
}

/**
 * @param {ChangeEvent} event
 */
function handleAwardTypeChanged(event) {
    const selectedValue = event.target.value;

    const unlockModeHardcore = 'h';
    const unlockModeSoftcore = 's';

    // A dictionary mapping the award kind to [awardType, unlockMode].
    /** @type Record<string, [number, string]> */
    const queryParamsMap = {
        mastered: [{{ $awardTypeMastery }}, unlockModeHardcore],
        completed: [{{ $awardTypeMastery }}, unlockModeSoftcore],
        'beaten-hardcore': [{{ $awardTypeGameBeaten }}, unlockModeHardcore],
        'beaten-softcore': [{{ $awardTypeGameBeaten }}, unlockModeSoftcore],
    }
    
    if (queryParamsMap[selectedValue]) {
        const [awardType, unlockMode] = queryParamsMap[selectedValue];
        window.updateUrlParameter(['t', 'm', 'o'], [awardType, unlockMode, '']);
    } else {
        // Revert back to "All".
        window.updateUrlParameter(['t', 'm'], ['', '']);
    }
}

/**
 * @param {ChangeEvent} event
 */
function handleUsersChanged(event) {
    const newValue = event.target.value === 'followed' ? 1 : 0;
    window.updateUrlParameter('f', newValue);
}
</script>

<div class="my-4">
    <div class="embedded p-4 mb-1 w-full">
        <p class="sr-only">Filters</p>

        <div class="grid sm:flex gap-y-4 sm:divide-x-2 divide-embed-highlight">
            <x-recent-awards.meta-date-filter :minAllowedDate="$minAllowedDate" :selectedDate="$selectedDate" />
            <x-recent-awards.meta-type-filter :selectedAwardType="$selectedAwardType" />

            @auth
                <x-recent-awards.meta-users-filter :selectedUsers="$selectedUsers" />
            @endauth
        </div>
    </div>

    <div class="w-full flex justify-end">
        <a class="text-2xs" href="/recentMastery.php">Clear all filters</a>
    </div>
</div>