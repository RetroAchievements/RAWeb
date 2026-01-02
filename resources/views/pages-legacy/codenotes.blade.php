<?php

use App\Enums\Permissions;
use App\Models\GameAchievementSet;
use App\Models\MemoryNote;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;

authenticateFromCookie($user, $permissions, $userDetails);

$gameID = requestInputSanitized('g', 1, 'integer');
$gameData = getGameData($gameID);
if (empty($gameData)) {
    abort(404);
}

$userModel = null;
if ($user) {
    $userModel = User::find($userDetails['id']);
}

$codeNotes = [];
getCodeNotes($gameID, $codeNotes);

$baseGameId = $gameID;
if (str_contains($gameData['Title'], "[Subset - ")) {
    $subsetGameAchievementSet = GameAchievementSet::query()
        ->whereIn('achievement_set_id',
            GameAchievementSet::where('game_id', $gameID)
                ->where('type', AchievementSetType::Core)
                ->pluck('achievement_set_id')
        )
        ->where('type', '!=', AchievementSetType::Core)
        // exclusive subsets can maintain their own notes
        ->where('type', '!=', AchievementSetType::Exclusive)
        ->first();

    if ($subsetGameAchievementSet) {
        $baseGameId = $subsetGameAchievementSet->game_id;
        
        if (empty($codeNotes)) {
            // no notes for subset. redirect to base set
            abort_with(redirect('codenotes.php?g=' . $baseGameId));
        }
    }
}

if ($permissions >= Permissions::Developer && $baseGameId !== $gameID) {
    // empty collection for subset selector
    $subsets = new Collection();

    // codeNotes are for the subset. rename them and fetch the code notes for the base set
    $subsetNotes = $codeNotes;
    $codeNotes = [];
    getCodeNotes($baseGameId, $codeNotes);

    // merge subset notes into the base set notes
    foreach ($subsetNotes as $subsetNote) {
        $found = false;
        foreach ($codeNotes as &$codeNote) {
            if ($codeNote['Address'] === $subsetNote['Address']) {
                $codeNote['SubsetUser'] = $subsetNote['User'];
                $codeNote['SubsetNote'] = $subsetNote['Note'];

                $found = true;
                break;
            }
        }
        if (!$found) {
            $codeNotes[] = [
                'Address' => $subsetNote['Address'],
                'User' => null,
                'Note' => '',
                'SubsetUser' => $subsetNote['User'],
                'SubsetNote' => $subsetNote['Note'],
            ];
        }
    }

    usort($codeNotes, fn($a, $b) => strcmp($a['Address'], $b['Address']));
    $hasSubsetNotes = true;
} else {
    $subsets = GameAchievementSet::query()
        ->whereIn('achievement_set_id',
            GameAchievementSet::where('game_id', $gameID)
                ->where('type', '!=', AchievementSetType::Core)
                ->pluck('achievement_set_id')
        )
        ->where('type', AchievementSetType::Core)
        ->with('game')
        ->whereHas('game.memoryNotes')
        ->get();
    $subsetNotes = new Collection();
    $hasSubsetNotes = false;
}

$codeNoteCount = count(array_filter($codeNotes, function ($x) { return $x['Note'] !== "" && $x['Note'] !== "''"; }));

$pageTitle = "Code Notes - {$gameData['Title']}";
?>
<x-app-layout :pageTitle="$pageTitle">
<script>
window.addEventListener('beforeunload', function (event) {
    const rows = document.querySelectorAll('tr.note-row');
    const hasDirtyTextareaEls = Array.from(rows).some((row) => {
        const noteDisplayEl = row.querySelector('.note-display');
        const noteEditEl = row.querySelector('.note-edit');
        
        if (!noteDisplayEl || !noteEditEl || noteEditEl.classList.contains('hidden')) {
            return false;
        }

        // "<br>" "<br />" "<br>\n"
        const originalValue = noteDisplayEl.innerHTML.replace(/<br\s*\/?>\n?/g, '\n');
        return originalValue !== noteEditEl.value;
    });

    if (hasDirtyTextareaEls) {
        const confirmationMessage = 'Any unsaved changes will be lost if you navigate away.';
        (event || window.event).returnValue = confirmationMessage;
        
        return confirmationMessage;
    }
});

/**
 * Toggle the editing state of a code note row and
 * show/hide the elements necessary to perform an edit.
 *
 * @param {HTMLTableRowElement} rowEl - The row element to toggle editing state for.
 * @param {boolean} isEditing - Whether the row is transitioning to edit mode or not.
 */
function setRowEditingEnabled(rowEl, isEditing) {
    const rowElementVisibilities = [
        { className: 'edit-btn', showWhen: !isEditing },
        { className: 'note-display', showWhen: !isEditing },
        { className: 'note-edit', showWhen: isEditing },
        { className: 'save-btn', showWhen: isEditing },
        { className: 'delete-btn', showWhen: isEditing },
        { className: 'cancel-btn', showWhen: isEditing },
    ];

    for (const visibility of rowElementVisibilities) {
        const targetEl = rowEl.querySelector(`.${visibility.className}`);

        if (visibility.showWhen === true) {
            targetEl.classList.remove('hidden');
        } else {
            targetEl.classList.add('hidden');
        }
    }
}

/**
 * Enable edit mode for a specific row.
 *
 * @param {number} rowIndex - The index of the row to enable edit mode for.
 */
function beginEditMode(rowIndex) {
    const rowEl = document.getElementById(`row-${rowIndex}`);
    setRowEditingEnabled(rowEl, true);

    const noteEditEl = rowEl.querySelector('.note-edit');

    // Calculate the number of rows based on the number of newline characters.
    const rowCount = (noteEditEl.value.match(/\n/g) || []).length + 1;

    // Set the textarea rows attribute, ensuring a minimum of 2 rows.
    noteEditEl.rows = Math.max(rowCount, 2);
}

<?php if ($hasSubsetNotes): ?>
function keepBaseNote(rowIndex) {
    const rowEl = document.getElementById(`row-${rowIndex}`);
    const addressHex = rowEl.querySelector('td[data-address]').dataset.address;

    showStatusMessage('Updating...');
    $.post('/request/game/merge-code-note.php', {
        address: parseInt(addressHex, 16),
        gameId: <?= $gameID ?>,
        keep: 0,
    }).done(() => {
        showStatusSuccess('Done!');

        const rowEl = document.getElementById(`row-${rowIndex}`);
        if (rowEl.querySelector('.keep-base-btn').classList.contains('btn-danger')) {
            // hide the whole row
            rowEl.classList.add('hidden');
        } else {
            // hide the merge UI and enable the edit button
            rowEl.querySelector('.keep-base-btn').classList.add('hidden');
            rowEl.querySelector('.keep-subset-btn').classList.add('hidden');
            rowEl.querySelector('.subset-note-display').classList.add('hidden');
            rowEl.querySelector('.subset-note-author').classList.add('hidden');
            rowEl.querySelector('.edit-btn').classList.remove('hidden');

            // update the counter
            const codeNoteCountEl = document.querySelector('.subset-code-note-count');
            const currentDisplayCount = Number(codeNoteCountEl.textContent);
            codeNoteCountEl.textContent = currentDisplayCount - 1;
        }
    }).fail(() => {
        showStatusFailure('There was a problem merging the code note.');
    });
}

function keepSubsetNote(rowIndex) {
    const rowEl = document.getElementById(`row-${rowIndex}`);
    const addressHex = rowEl.querySelector('td[data-address]').dataset.address;

    showStatusMessage('Updating...');
    $.post('/request/game/merge-code-note.php', {
        address: parseInt(addressHex, 16),
        gameId: <?= $gameID ?>,
        keep: 1,
    }).done(() => {
        showStatusSuccess('Done!');

        // hide the merge UI and enable the edit button
        const rowEl = document.getElementById(`row-${rowIndex}`);
        rowEl.querySelector('.keep-base-btn').classList.add('hidden');
        rowEl.querySelector('.keep-subset-btn').classList.add('hidden');
        rowEl.querySelector('.subset-note-display').classList.add('hidden');
        rowEl.querySelector('.subset-note-author').classList.add('hidden');
        rowEl.querySelector('.edit-btn').classList.remove('hidden');

        // move the subset UI into the base set UI
        rowEl.querySelector('.note-display').innerHTML = rowEl.querySelector('.subset-note-display').innerHTML;
        rowEl.querySelector('.note-edit').textContent = rowEl.querySelector('.subset-note-display').textContent;
        rowEl.querySelector('.note-author-avatar').innerHTML = rowEl.querySelector('.subset-note-author').innerHTML;

        // update the counter
        const codeNoteCountEl = document.querySelector('.subset-code-note-count');
        const currentDisplayCount = Number(codeNoteCountEl.textContent);
        codeNoteCountEl.textContent = currentDisplayCount - 1;
    }).fail(() => {
        showStatusFailure('There was a problem merging the code note.');
    });
}
<?php endif ?>

/**
 * Cancel edit mode for a specific row.
 *
 * @param {number} rowIndex - The index of the row to cancel edit mode for.
 */
function cancelEditMode(rowIndex) {
    const rowEl = document.getElementById(`row-${rowIndex}`);

    // Restore the original value so unsaved edits are not persisted.
    const noteDisplayEl = rowEl.querySelector('.note-display');
    const noteEditEl = rowEl.querySelector('.note-edit');
    // "<br>" "<br />" "<br>\n"
    const originalValue = noteDisplayEl ? noteDisplayEl.innerHTML.replace(/<br\s*\/?>\n?/g, '\n') : '';

    if (originalValue !== noteEditEl.value && !confirm('Are you sure you want to discard your unsaved changes?')) {
        return;
    }

    noteEditEl.value = originalValue;

    setRowEditingEnabled(rowEl, false);
}

/**
 * Did the user change the contents of the note?
 *
 * @param {number} rowIndex - The index of the row to compare view and edit values.
 * @param {string} currentNoteValue - The new note value given by the user.
 */
function isSavedNoteDifferent(rowIndex, newNoteValue) {
    // Get the original value from the view-mode DOM content,
    // which we'll compare against the user's editable content.
    const rowEl = document.getElementById(`row-${rowIndex}`);
    const noteDisplayEl = rowEl.querySelector('.note-display');
    const originalNoteValue = noteDisplayEl.innerHTML;

    // The original note has invisible "<br>" tags for each line break.
    const noteValueWithLineBreaks = newNoteValue.replace(/\n/g, '<br>\n');

    return originalNoteValue !== noteValueWithLineBreaks;
}

/**
 * @param {number} rowIndex - The index of the row to delete the note for.
 */
function deleteCodeNote(rowIndex) {
    if (!confirm('Are you sure you want to delete this note? It will be irreversibly lost.')) {
        return;
    }

    saveCodeNote(rowIndex, true);
}

/**
 * Save the updated note for a specific row and
 * go back to view mode.
 *
 * @param {number} rowIndex - The index of the row to save the note for.
 * @param {boolean} isDeleting - Are we deleting this code note?
 */
function saveCodeNote(rowIndex, isDeleting = false) {
    const rowEl = document.getElementById(`row-${rowIndex}`);
    const noteDisplayEl = rowEl.querySelector('.note-display');
    const authorAvatarCellEl = rowEl.querySelector('.note-author-avatar');
    const noteEditEl = rowEl.querySelector('.note-edit');

    const addressHex = rowEl.querySelector('td[data-address]').dataset.address;
    const currentAuthor = rowEl.querySelector('td[data-current-author]').dataset.currentAuthor;

    const currentUsername = '<?= $user ?>';

    // If the user didn't actually change anything in the note but still
    // pressed the Save button, treat this like it's a cancel.
    if (!isDeleting && !isSavedNoteDifferent(rowIndex, noteEditEl.value)) {
        cancelEditMode(rowIndex);
        return;
    }

    // If the code note was authored by a different user, make sure the
    // current user is fine with taking ownership of this note.
    if (
        !isDeleting &&
        currentAuthor !== currentUsername &&
        !confirm('Are you sure you want to replace this note? You will become the author of this note.')
    ) {
        return;
    }

    showStatusMessage('Updating...');
    $.post('/request/game/update-code-note.php', {
        // Make sure line endings are normalized to "\r\n" before storing in the DB.
        // Otherwise they'll look correct in the web UI, but won't look correct
        // in the RAIntegration tooling.
        note: isDeleting ? null : noteEditEl.value.replace(/\n/g, '\r\n'),
        // Addresses are stored as base 10 numbers in the DB, not base 16.
        address: parseInt(addressHex, 16),
        gameId: <?= $baseGameId ?>,
    }).done(() => {
        showStatusSuccess('Done!');

        // Before bailing from edit mode, make sure all new values for
        // the note (the text content and author avatar) are properly
        // displayed in the UI.
        if (isDeleting) {
            rowEl.remove();

            const codeNoteCountEl = document.querySelector('.code-note-count');
            const currentDisplayCount = Number(codeNoteCountEl.textContent);
            codeNoteCountEl.textContent = currentDisplayCount - 1;
        } else {
            const noteValueWithLineBreaks = noteEditEl.value.replace(/\n/g, '<br />');
            noteDisplayEl.innerHTML = noteValueWithLineBreaks;

            const avatarImageEl = authorAvatarCellEl.querySelector('img');
            avatarImageEl.src = mediaAsset(`/UserPic/${currentUsername}.png`);

            rowEl.querySelector('td[data-current-author]').dataset.currentAuthor = currentUsername;

            const authorAvatarSpan = authorAvatarCellEl.querySelector('span.inline.whitespace-nowrap');
            authorAvatarSpan.removeAttribute('@mouseover');
        }

        // Now it's safe to bail.
        setRowEditingEnabled(rowEl, false);
    }).fail(() => {
        showStatusFailure('There was a problem updating the code note.');
    });
}
</script>
    <div class='navpath'>
        <?= renderGameBreadcrumb($gameData) ?>
        &raquo; <b>Code Notes</b>
    </div>
    <h3>Code Notes</h3>
    <?= gameAvatar($gameData, iconSize: 64); ?>
    <?php
    if ($subsets->count() > 0)
    {
        echo "<br/><br/>Subset Notes:";
        foreach ($subsets as $subset) {
            echo "<br/>";
            $icon = "<img decoding='async' width='24' height='24' src='{$subset->game->badgeUrl}' class='badgeimg'>";
            $link = "codenotes.php?g=" . $subset->game_id;
            $label = Blade::render("<x-game-title :rawTitle=\"\$rawTitle\" />", ['rawTitle' => $subset->game->title]);
            echo "<span class='inline'><a class='inline-block' href='$link'>$icon $label</a></span>";
        }
    }
    ?>
    <br/>
    <br/>
    <p>The RetroAchievements addressing scheme for most systems is to access the system memory
    at address $00000000, immediately followed by the cartridge memory. As such, the addresses
    displayed below may not directly correspond to the addresses on the real hardware.</p>
    <br/>
    <p>There are currently <span class='font-bold code-note-count'><?= $codeNoteCount ?></span> code notes for this game.</p>
    <?php
    if ($hasSubsetNotes) {
        $numSubsetNotes = count($subsetNotes);
        if ($numSubsetNotes > 0) {
            echo "<p><span class='font-bold subset-code-note-count'>$numSubsetNotes</span> subset notes beed to be merged.</p>";
        }
    }
    if (isset($user) && $permissions >= Permissions::Registered) {
        RenderCodeNotes($codeNotes, $userModel, $permissions, $hasSubsetNotes);
    }
    ?>
</x-app-layout>
