<?php

use App\Enums\Permissions;
use App\Models\User;

function RenderCodeNotes(array $codeNotes, ?User $editingUser = null, ?int $editingPermissions = null, ?bool $hasSubsetNotes = false): void
{
    $isEditable = $editingUser && $editingPermissions >= Permissions::JuniorDeveloper;

    echo "<table class='table-highlight'>";

    echo "<thead>";
    echo "<tr class='do-not-highlight'>";
    echo "<th style='font-size:100%; width:10%'>Mem</th>";
    if ($hasSubsetNotes) {
        echo "<th style='font-size:100%; width:35%'>Base Set Note</th>";
        echo "<th style='font-size:100%; width:10%'>Base Set Note Author</th>";
        echo "<th style='font-size:100%; width:35%'>Subset Note</th>";
        echo "<th style='font-size:100%; width:10%'>Subset Note Author</th>";
    } else {
        echo "<th style='font-size:100%;'>Note</th>";
        echo "<th style='font-size:100%; width:10%'>Author</th>";
    }
    if ($isEditable) {
        echo "<th>Dev</th>";
    }
    echo "</tr>";
    echo "</thead>";

    echo "<tbody>";

    $rowIndex = 0;
    foreach ($codeNotes as $nextCodeNote) {
        if (empty(trim($nextCodeNote['Note'])) || $nextCodeNote['Note'] == "''") {
            if (!array_key_exists('SubsetUser', $nextCodeNote)) {
                continue;
            }
        }

        $canEditNote = (
            $editingPermissions >= Permissions::Developer
            || ($editingPermissions === Permissions::JuniorDeveloper && $nextCodeNote['User'] === $editingUser?->display_name)
        );

        echo "<tr id='row-$rowIndex' class='note-row'>";

        $addr = $nextCodeNote['Address'];
        $addrInt = hexdec($addr);

        $addrFormatted = sprintf("%04x", $addrInt);
        $originalMemNote = $nextCodeNote['Note'];

        sanitize_outputs($originalMemNote);

        $memNote = nl2br($originalMemNote);

        if (array_key_exists('SubsetUser', $nextCodeNote)) {
            $subsetMemNote = $nextCodeNote['SubsetNote'] ?? '';
            sanitize_outputs($subsetMemNote);
            $subsetMemNote = nl2br($subsetMemNote);

            $keepButtonExtra = '';
            $keepText = 'Keep Base Set Note';
            if (empty(trim($nextCodeNote['Note'])) || $nextCodeNote['Note'] == "''") {
                $keepText = 'Discard Subset Note';
                $keepButtonExtra = ' btn-danger';
                $memNote = "<span class='text-text-muted'><i>No base set note</i></span>";
            }
            $keepBaseButton = "<button class='btn keep-base-btn$keepButtonExtra inline' type='button' onclick='keepBaseNote($rowIndex)'>$keepText</button>";
        } else {
            $subsetMemNote = '';
            $keepBaseButton = '';
        }

        echo "<td data-address='$addr'>";
        echo "<span class='font-mono'>0x$addrFormatted</span>";
        echo "</td>";

        echo <<<HTML
            <td>
                <div class="font-mono note-display block" style="word-break: break-word;">$memNote</div>
                <textarea class="w-full font-mono note-edit hidden">$originalMemNote</textarea>
                <div class="mt-[6px] flex justify-between">
                    <button class="btn save-btn hidden" type="button" onclick="saveCodeNote($rowIndex)">Save</button>
                    <button class="btn delete-btn btn-danger hidden" type="button" onclick="deleteCodeNote($rowIndex)">Delete</button>
                    $keepBaseButton
                </div>
            </td>
        HTML;

        echo "<td class='note-author-avatar' data-current-author='" . $nextCodeNote['User'] . "'>";
        echo userAvatar($nextCodeNote['User'], label: false, iconSize: 24);
        echo "</td>";

        if ($hasSubsetNotes) {
            if (array_key_exists('SubsetUser', $nextCodeNote)) {
                echo <<<HTML
                    <td>
                        <div class="font-mono subset-note-display block" style="word-break: break-word;">$subsetMemNote</div>
                        <button class='btn keep-subset-btn inline' type='button' onclick='keepSubsetNote($rowIndex)'>Keep Subset Note</button>
                    </td>
                HTML;

                echo "<td class='subnote-author-avatar'><span class='subset-note-author'>";
                echo userAvatar($nextCodeNote['SubsetUser'], label: false, iconSize: 24);
                echo "</span></td>";
            } else {
                echo "<td></td><td></td>";
            }
        }

        if ($canEditNote) {
            $editClass = array_key_exists('SubsetUser', $nextCodeNote) ? 'hidden' : 'inline';
            echo "<td>";
            echo "<button class='btn edit-btn $editClass' type='button' onclick='beginEditMode($rowIndex)'>Edit</button>";
            echo "<button class='btn cancel-btn hidden' type='button' onclick='cancelEditMode($rowIndex)'>Cancel</button>";
            echo "</td>";
        } elseif ($isEditable) {
            echo "<td></td>";
        }

        echo "</tr>";

        $rowIndex++;
    }

    echo "</tbody></table>";
}
