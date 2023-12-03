@props([
    'canHaveBeatenTypes' => true,
    'gameId' => 0,
    'modificationLevel' => 'none', // 'none' | 'partial' | 'full'
    'isManagingCoreAchievements' => true,
])

<?php
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;

$progressionType = AchievementType::Progression;
$winConditionType = AchievementType::WinCondition;
$missableType = AchievementType::Missable;
$officialFlag = AchievementFlag::OfficialCore;
$unofficialFlag = AchievementFlag::Unofficial;
?>

@if ($modificationLevel !== 'none')
    <script>
    function toolboxComponent() {
        return {
            areCodeRowsHidden: true,

            /**
             * @param {'flag' | 'type'} property
             * @param {3 | 5 | 'progression' | 'win_condition' | 'missable' | null} newValue
             * @param {number} selectedCount
             */
            getConfirmMessage(property, newValue, selectedCount) {
                let message = 'Are you sure you want to make this change?';

                if (property === 'flag') {
                    if (newValue === {{ $officialFlag }}) {
                        message = `Are you sure you want to promote ${selectedCount === 1 ? 'this achievement' : 'these achievements'}?`;
                    } else {
                        message = `Are you sure you want to demote ${selectedCount === 1 ? 'this achievement' : 'these achievements'}?`;
                    }
                }

                if (property === 'type') {
                    if (newValue === '{{ $progressionType }}') {
                        message = `Are you sure you want to set ${selectedCount === 1 ? 'this achievement' : 'these achievements'} to Progression?`;
                    } else if (newValue === '{{ $winConditionType }}') {
                        message = `Are you sure you want to set ${selectedCount === 1 ? 'this achievement' : 'these achievements'} to Win Condition?`;
                    } else if (newValue === '{{ $missableType }}') {
                        message = `Are you sure you want to set ${selectedCount === 1 ? 'this achievement' : 'these achievements'} to Missable?`;
                    } else {
                        message = `Are you sure you want to remove the type from ${selectedCount === 1 ? 'this achievement' : 'these achievements'}?`;
                    }
                }

                return message;
            },

            toggleAllCodeRows() {
                const codeRowEls = document.querySelectorAll('.code-row');

                for (const codeRowEl of codeRowEls) {
                    if (this.areCodeRowsHidden) {
                        codeRowEl.classList.remove('hidden');
                    } else {
                        codeRowEl.classList.add('hidden');
                    }
                }

                this.areCodeRowsHidden = !this.areCodeRowsHidden;
            },

            refreshPage() {
                window.location.reload();
            },

            /**
             * @param {'flag' | 'type'} property
             * @param {3 | 5 | 'progression' | 'win_condition' | 'missable' | null} newValue
             */
            updateAchievementsProperty(property, newValue) {
                // Creates an array of checked achievement IDs and sends it to the updateAchievements function
                const checkboxes = document.querySelectorAll('[name^=\'achievement\']');
                const achievements = [];
                for (let i = 0, n = checkboxes.length; i < n; i += 1) {
                    if (checkboxes[i].checked) {
                        achievements.push(checkboxes[i].getAttribute('value'));
                    }
                }

                if (!achievements.length || !confirm(this.getConfirmMessage(property, newValue, achievements.length))) {
                    return;
                }

                window.showStatusMessage('Updating...');

                const requestUrl = property === 'flag'
                    ? '/request/achievement/update-flag.php'
                    : '/request/achievement/update-type.php';

                $.post(requestUrl, {
                    achievements,
                    [property]: newValue
                }).done(function () {
                    location.reload();
                });
            },
        }
    }
    </script>

    <h3>Toolbox</h3>

    <div
        class="flex flex-col gap-y-1 [&>button]:flex [&>a]:flex [&>button]:justify-center [&>a]:justify-center [&>button]:py-2 [&>a]:py-2 [&>a]:w-full"
        x-data="toolboxComponent()"
    >
        <button class="btn" @click="refreshPage">Refresh Page</button>

        @if ($modificationLevel === 'full')
            <button
                class="btn"
                @click="updateAchievementsProperty('flag', {{ $isManagingCoreAchievements ? $unofficialFlag : $officialFlag }})"
            >
                @if ($isManagingCoreAchievements)
                    Demote Selected
                @else
                    Promote Selected
                @endif
            </button>
        @endif

        <a
            class="btn"
            href="/achievementinspector.php?g={{ $gameId }}{{ $isManagingCoreAchievements ? '&f=' . $unofficialFlag : '' }}"
        >
            @if ($isManagingCoreAchievements)
                Unofficial Achievement Inspector
            @else
                Core Achievement Inspector
            @endif
        </a>

        @if ($canHaveBeatenTypes)
            <button
                class="btn"
                @click="updateAchievementsProperty('type', '{{ $progressionType }}')"
            >
                Set Selected to Progression
            </button>

            <button
                class="btn"
                @click="updateAchievementsProperty('type', '{{ $winConditionType }}')"
            >
                Set Selected to Win Condition
            </button>
        @endif

        <button
            class="btn"
            @click="updateAchievementsProperty('type', '{{ $missableType }}')"
        >
            Set Selected to Missable
        </button>

        <button
            class="btn"
            @click="updateAchievementsProperty('type', null)"
        >
            Set Selected to No Type
        </button>

        @if ($modificationLevel === 'full')
            <button class="btn" @click="toggleAllCodeRows">Toggle Code Rows</button>
        @endif

        <a class="btn" href="/achievementinspector.php">Back to List</a>
    </div>
@endif
