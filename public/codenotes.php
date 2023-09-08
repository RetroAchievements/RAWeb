<?php

use App\Site\Enums\Permissions;

authenticateFromCookie($user, $permissions, $userDetails);

$gameID = requestInputSanitized('g', 1, 'integer');
$gameData = getGameData($gameID);
if (empty($gameData)) {
    abort(404);
}

getCodeNotes($gameID, $codeNotes);
$codeNoteCount = count(array_filter($codeNotes, function ($x) { return $x['Note'] !== "" && $x['Note'] !== "''"; }));

RenderContentStart('Code Notes - ' . $gameData['Title']);
?>
<script>
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
    const originalValue = noteDisplayEl.innerHTML.replace(/<br\s*\/?>\n?/g, '\n');

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
        gameId: <?= $gameID ?>,
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
<article>
    <div class='navpath'>
        <?= renderGameBreadcrumb($gameData) ?>
        &raquo; <b>Code Notes</b>
    </div>
    <h3>Code Notes</h3>
    <?= gameAvatar($gameData, iconSize: 64); ?>
    <br/>
    <br/>
    <p>The RetroAchievements addressing scheme for most systems is to access the system memory
    at address $00000000, immediately followed by the cartridge memory. As such, the addresses
    displayed below may not directly correspond to the addresses on the real hardware.</p>
    <br/>
    <p>There are currently <span class='font-bold code-note-count'><?= $codeNoteCount ?></span> code notes for this game.</p>
    <?php
    if (isset($user) && $permissions >= Permissions::Registered) {
        RenderCodeNotes($codeNotes, $user, $permissions);
    }
    ?>
</article>
<?php RenderContentEnd(); ?>
