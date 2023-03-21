<?php

use LegacyApp\Site\Enums\Permissions;

authenticateFromCookie($user, $permissions, $userDetails);

$gameID = requestInputSanitized('g', 1, 'integer');
$gameData = getGameData($gameID);

sanitize_outputs(
    $gameData['Title'],
    $gameData['ConsoleName'],
);

getCodeNotes($gameID, $codeNotes);

RenderContentStart('Code Notes - ' . htmlspecialchars_decode($gameData['Title']));
?>
<script>
/**
 * Toggle the editing state of a code note row and 
 * show/hide the elements necessary to perform an edit.
 * 
 * @param {Element} rowEl - The row element to toggle editing state for.
 * @param {boolean} isEditing - Whether the row is transitioning to edit mode or not.
 */
function setRowEditingEnabled(rowEl, isEditing) {
    const rowElementVisibilities = [
        { className: 'edit-btn', showWhen: !isEditing },
        { className: 'note-display', showWhen: !isEditing },
        { className: 'note-edit', showWhen: isEditing },
        { className: 'save-btn', showWhen: isEditing },
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
    setRowEditingEnabled(rowEl, false);
}

/**
 * Save the updated note for a specific row and 
 * go back to view mode.
 * 
 * @param {number} rowIndex - The index of the row to save the note for.
 */
function saveCodeNote(rowIndex) {
    const rowEl = document.getElementById(`row-${rowIndex}`);
    const noteDisplayEl = rowEl.querySelector('.note-display');
    const authorAvatarCellEl = rowEl.querySelector('.note-author-avatar');
    const noteEditEl = rowEl.querySelector('.note-edit');

    const addressHex = rowEl.querySelector('td[data-address]').dataset.address;

    showStatusMessage('Updating...');
    $.post('/request/game/update-code-note.php', {
        note: noteEditEl.value,
        // Addresses are stored as base 10 numbers in the DB, not base 16.
        address: parseInt(addressHex, 16),
        gameId: <?= $gameID ?>,
    }).done(() => {
        showStatusSuccess('Done!');

        // Before bailing from edit mode, make sure all new values for
        // the note (the text content and author avatar) are properly
        // displayed in the UI.
        const currentUsername = '<?= $user ?>';

        const noteValueWithLineBreaks = noteEditEl.value.replace(/\n/g, '<br />');
        noteDisplayEl.innerHTML = noteValueWithLineBreaks;

        const avatarImageEl = authorAvatarCellEl.querySelector('img');
        avatarImageEl.src = mediaAsset(`/UserPic/${currentUsername}.png`);

        const authorAvatarSpan = authorAvatarCellEl.querySelector('span.inline.whitespace-nowrap');
        const tooltipOnMouseOverAttr = authorAvatarSpan.getAttribute('onmouseover');
        const updatedOnMouseOverAttr = tooltipOnMouseOverAttr.replace(
            /loadCard\(this, 'user', '([^']*)', ''\)/,
            `loadCard(this, 'user', '${currentUsername}', '')`
        );
        authorAvatarSpan.setAttribute('onmouseover', updatedOnMouseOverAttr);

        // Now it's safe to bail.
        setRowEditingEnabled(rowEl, false);
    });
}
</script>
<div id='mainpage'>
    <div id="fullcontainer">
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
        <?php
        if (isset($gameData) && isset($user) && $permissions >= Permissions::Registered) {
            RenderCodeNotes($codeNotes, $permissions >= Permissions::JuniorDeveloper);
        }
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
