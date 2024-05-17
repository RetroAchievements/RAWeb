@props([
    'userJoinedGamesAndAwards' => [],
    'username' => '',
])

<?php
$prefersHiddenUserCompletedSets = request()->cookie('prefers_hidden_user_completed_sets') === 'true';
?>

<div id="completedgames" class="component mb-6">
    <h3>Completion Progress</h3>

    <div class="w-full flex items-center justify-between mb-2">
        <label class="flex items-center gap-x-1 select-none transition lg:active:scale-95 cursor-pointer">
            <input
                id="hide-user-completed-sets-checkbox"
                type="checkbox"
                autocomplete="off"
                class="cursor-pointer"
                onchange="toggleUserCompletedSetsVisibility()"
                @if ($prefersHiddenUserCompletedSets) checked @endif
            >
                Hide completed games
            </input>
        </label>

        <a href="{{ route('user.completion-progress', ['user' => $username]) }}">more...</a>
    </div>

    <div id="usercompletedgamescomponent">
        <table class="table-highlight">
            <tbody>
                @foreach ($userJoinedGamesAndAwards as $completionProgressEntity)
                    @if (!$completionProgressEntity['NumAwarded'])
                        @continue
                    @endif

                    <x-user.completion-progress.user-completion-progress-row
                        :completionProgressEntity="$completionProgressEntity"
                    />
                @endforeach
            </tbody>
        </table>
    </div>
</div>
