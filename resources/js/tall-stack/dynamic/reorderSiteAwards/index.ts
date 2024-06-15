import { cancelAutoscroll } from './cancelAutoscroll';
import { reorderSiteAwardsStore as store } from './reorderSiteAwardsStore';

/**
 * Sets if a row should have an outline surrounding it.
 * @param {HTMLTableRowElement} rowEl The row to add or remove an outline from.
 * @param {boolean} isOutlineVisible Whether the outline should be added or removed.
 */
function setRowOutlineVisibility(rowEl: HTMLTableRowElement, isOutlineVisible: boolean) {
  const outlineClassNames = ['outline', 'outline-menu-link', 'outline-offset-2'];

  if (isOutlineVisible) {
    rowEl.classList.add(...outlineClassNames);
  } else {
    rowEl.classList.remove(...outlineClassNames);
  }
}

export function handleRowDragStart(event: DragEvent) {
  store.currentGrabbedRowEl = event.target as HTMLTableRowElement;
  store.currentGrabbedRowEl.style.opacity = '0.3';

  const openTooltipEl = document.querySelector('.tooltip-body');
  if (openTooltipEl) {
    openTooltipEl.remove();
  }
}

export function handleRowDragEnd(event: DragEvent) {
  store.currentGrabbedRowEl = null;
  (event.target as HTMLTableRowElement).style.opacity = '1';
}

/**
 * Handles the row drag enter event, which occurs when a dragged row enters
 * another row's boundary.
 * @param {DragEvent} event
 */
export function handleRowDragEnter(event: DragEvent) {
  const targetRowEl = (event.target as HTMLTableRowElement).closest('tr');

  const grabbedRowEl = store.currentGrabbedRowEl ? store.currentGrabbedRowEl.closest('tr') : null;

  // For type-safety, assert that both rows actually exist.
  if (grabbedRowEl && targetRowEl) {
    const isHoveredRowInSameTable = grabbedRowEl.parentNode === targetRowEl?.parentNode;
    const isAwardHiddenChecked = isRowHidden(targetRowEl);

    // Add border styling to the target row if it's in the same table and not hidden.
    if (isHoveredRowInSameTable && !isAwardHiddenChecked) {
      setRowOutlineVisibility(targetRowEl, true);
    }
  }
}

export function handleRowDragLeave(event: DragEvent) {
  const targetRowEl = (event.currentTarget as HTMLTableRowElement).closest('tr');
  const relatedTarget = event.relatedTarget as HTMLElement;

  // Ignore the event if the related target is a child of the current target.
  // This prevents an issue where the drag outline disappears when dragging
  // over a cell in the table row.
  if (targetRowEl && relatedTarget && targetRowEl.contains(relatedTarget)) {
    return;
  }

  if (targetRowEl) {
    setRowOutlineVisibility(targetRowEl, false);
  }
}

/**
 * Handles the row drop event during a drag and drop operation on a table row.
 * @param {DragEvent} event
 */
export function handleRowDrop(event: DragEvent) {
  event.preventDefault();
  const targetEl = event.target as HTMLTableRowElement;

  const dropTargetEl = targetEl.closest('tr');
  const isDropTargetHidden = isRowHidden(dropTargetEl);

  const grabbedRowEl = store.currentGrabbedRowEl ? store.currentGrabbedRowEl.closest('tr') : null;

  if (grabbedRowEl && dropTargetEl && !isDropTargetHidden) {
    const draggedTableEl = grabbedRowEl.closest('table');
    const dropTargetTableEl = dropTargetEl.closest('table');

    // Ensure both rows belong to the same table.
    if (draggedTableEl === dropTargetTableEl) {
      const draggedRowIndex = Array.from(grabbedRowEl.parentNode?.children ?? []).indexOf(
        grabbedRowEl,
      );
      const dropTargetIndex = Array.from(dropTargetEl.parentNode?.children ?? []).indexOf(
        dropTargetEl,
      );

      // Don't do anything if the user drops the row back into place.
      if (draggedRowIndex !== dropTargetIndex) {
        if (draggedRowIndex < dropTargetIndex) {
          dropTargetEl.parentNode?.insertBefore(grabbedRowEl, dropTargetEl.nextSibling);
        } else {
          dropTargetEl.parentNode?.insertBefore(grabbedRowEl, dropTargetEl);
        }

        // When this flag is raised, the browser will notify the user if
        // they attempt to leave the page with any unsaved changes.
        store.isFormDirty = true;
      }
    }
  }

  if (dropTargetEl) {
    setRowOutlineVisibility(dropTargetEl, false);
  }

  // If we're autoscrolling, immediately halt.
  cancelAutoscroll();
}

/**
 * Get the index of the last hidden row in the table.
 * @param {HTMLElement} tbodyEl - The table body element containing the rows.
 * @returns {number} - The index of the last hidden row in the table.
 */
export function getLastHiddenRowIndex(tbodyEl: HTMLTableSectionElement) {
  const totalRowCount = tbodyEl.children.length;

  // Loop through the table rows, starting from the first row.
  for (let rowIndex = 0; rowIndex < totalRowCount; rowIndex += 1) {
    const rowEl = tbodyEl.children[rowIndex];
    const isHiddenCheckboxEl = rowEl.querySelector<HTMLInputElement>('input[type="checkbox"]');

    // If the current row's checkbox is not checked, return the index of the previous row.
    if (isHiddenCheckboxEl && !isHiddenCheckboxEl.checked) {
      return rowIndex - 1;
    }
  }

  // If all rows are hidden, return the index of the last row.
  return totalRowCount - 1;
}

/**
 * Adjusts the newIndex based on hidden rows and moveBy value.
 * Ensures that the target row cannot move above the hidden rows.
 *
 * @param {number} newIndex - The calculated new index after moving the row.
 * @param {number} lastHiddenRowIndex - The index of the last hidden row.
 * @param {number} moveBy - The number of rows the target row should move.
 * @returns {number} - The adjusted new index for the target row.
 */
export function adjustNewIndex(newIndex: number, lastHiddenRowIndex: number, moveBy: number) {
  // Prevent the row from moving above hidden rows when moving upwards
  if (moveBy < 0 && newIndex <= lastHiddenRowIndex) {
    newIndex = lastHiddenRowIndex + 1;
  }

  return newIndex;
}

/**
 * Checks if a table row is hidden.
 *
 * @param {HTMLTableRowElement} rowEl - The row element to check if hidden.
 * @returns {boolean} - True if the row is hidden, otherwise false.
 */
export function isRowHidden(rowEl: HTMLTableRowElement | null) {
  if (!rowEl) return false;

  return rowEl.querySelector<HTMLInputElement>('input[type="checkbox"]')?.checked ?? false;
}

/**
 * Build an ordered array of award kinds based on the user-selected order of each section.
 * @throws {Error} If there are duplicate order numbers for different sections.
 * @returns {string[]} - An ordered array of award kinds, eg `["game", "event"]`
 */
export function buildSectionsOrderList(): string[] {
  const sectionOrderSelectEls =
    document.querySelectorAll<HTMLSelectElement>('select[data-award-kind]');
  const selectedValues: Record<string, string> = {};

  let hasDuplicates = false;
  const sectionsOrderList: string[] = [];

  // Store the selected order and its corresponding award kind in the selectedValues object.
  sectionOrderSelectEls.forEach((selectEl) => {
    const awardKind = selectEl.getAttribute('data-award-kind');
    const currentValue = selectEl.value;

    if (selectedValues[currentValue]) {
      hasDuplicates = true;
    } else if (awardKind) {
      selectedValues[currentValue] = awardKind;
    }
  });

  // If the user picked multiple of the same number, throw an error.
  if (hasDuplicates) {
    throw new Error('Please ensure each section has a unique order number.');
  }

  // Build the ordered list of award sections.
  Object.keys(selectedValues)
    .sort()
    .forEach((key) => {
      sectionsOrderList.push(selectedValues[key]);
    });

  return sectionsOrderList;
}

type MappedTableRow = {
  isHidden: boolean;
  type: string;
  data: string;
  extra: string;
  kind: string;
};

/**
 * Compute display order values for an array of mapped table rows based on the user-defined award ordering.
 * @param {MappedTableRow[]} mappedTableRows - The array of mapped table rows.
 * @returns {MappedTableRow[]} - The updated list of mapped table rows with computed display order values.
 */
export function computeDisplayOrderValues(mappedTableRows: MappedTableRow[]) {
  const sectionsOrder = buildSectionsOrderList();

  // Sort the rows by the user-defined sections ordering.
  const sortedBySectionsOrder: MappedTableRow[] = [];
  sectionsOrder.forEach((targetSection) => {
    const sectionRows = mappedTableRows.filter((row) => row.kind === targetSection);
    sortedBySectionsOrder.push(...sectionRows);
  });

  // Compute display order values for each row based on their position and section.
  const withDisplayOrderValues = sortedBySectionsOrder.map((row, rowIndex) => {
    let displayOrder = -1; // Hidden by default

    if (!row.isHidden) {
      // The first group will have an offset of 0.
      // The second group will have an offset of 3000.
      // The third group will have an offset of 6000.
      // etc...
      const groupOffsetBoost =
        sectionsOrder.findIndex((sectionName) => sectionName === row.kind) * 3000;

      // Set the display order value considering the row index, offset, and an arbitrary shift of 20.
      displayOrder = rowIndex + 20 + groupOffsetBoost;
    }

    return {
      ...row,
      number: displayOrder,
    };
  });

  // Now properly order the sections by fixing the displayOrder value
  // of the first `number` for each visible section row.
  for (let i = 0; i < sectionsOrder.length; i += 1) {
    const currentSectionKind = sectionsOrder[i];

    for (let j = 0; j < withDisplayOrderValues.length; j += 1) {
      const row = withDisplayOrderValues[j];
      if (row.number !== -1 && row.kind === currentSectionKind) {
        row.number = i;
        break;
      }
    }
  }

  // Remove all unneeded fields to minimize the size of the upcoming POST call.
  return withDisplayOrderValues.map((award) => ({
    type: award.type,
    data: award.data,
    extra: award.extra,
    number: award.number,
  }));
}

/**
 * Toggle the visibility of award movement buttons and the draggable attribute of a row
 * when the Hidden checkbox is checked or unchecked.
 * @param {MouseEvent} event The MouseEvent representing the change event on the hidden checkbox.
 * @param {number} rowIndex The index of the row being modified.
 */
export function handleRowHiddenCheckedChange(event: MouseEvent, rowIndex: number) {
  const isHiddenChecked = (event.target as HTMLInputElement).checked;

  const targetRowEl = document.querySelector<HTMLTableRowElement>(
    `tr[data-row-index="${rowIndex}"]`,
  );

  if (targetRowEl) {
    const buttonsContainerEl = targetRowEl.querySelector<HTMLDivElement>('.award-movement-buttons');

    // Toggle the visibility of award movement buttons and
    // the draggable attribute of the row.
    if (buttonsContainerEl) {
      if (isHiddenChecked) {
        targetRowEl.classList.remove('cursor-grab');
        targetRowEl.setAttribute('draggable', 'false');
        buttonsContainerEl.style.opacity = '0';

        // The buttons are invisible, but make sure they are also disabled.
        buttonsContainerEl.querySelectorAll('button').forEach((buttonEl) => {
          buttonEl.disabled = true;
        });
      } else {
        targetRowEl.classList.add('cursor-grab');
        targetRowEl.setAttribute('draggable', 'true');
        buttonsContainerEl.style.opacity = '100';

        // Re-enable the buttons so they can be used again.
        buttonsContainerEl.querySelectorAll('button').forEach((buttonEl) => {
          buttonEl.removeAttribute('disabled');
        });
      }
    }

    store.isFormDirty = true;

    // Update the opacity of the row's cells based on
    // the hidden checkbox status.
    const allTdEls = targetRowEl.querySelectorAll('td');
    allTdEls.forEach((tdEl) => {
      if (isHiddenChecked && !tdEl.classList.contains('!opacity-100')) {
        tdEl.classList.add('opacity-40');
      } else {
        tdEl.classList.remove('opacity-40');
      }
    });
  }
}

export function handleDisplayOrderChange() {
  store.isFormDirty = true;
}

/**
 * Move all hidden rows to the top of their respective tables.
 */
export function moveHiddenRowsToTop() {
  const tableEls = document.querySelectorAll('table');

  tableEls.forEach((tableEl) => {
    const rowEls = tableEl.querySelectorAll('tr');
    const hiddenRows: HTMLTableRowElement[] = [];
    const visibleRows: HTMLTableRowElement[] = [];

    rowEls.forEach((rowEl) => {
      const checkboxEl = rowEl.querySelector<HTMLInputElement>('input[name$="-is-hidden"]');
      if (checkboxEl && checkboxEl.checked) {
        hiddenRows.push(rowEl);
      } else {
        visibleRows.push(rowEl);
      }
    });

    // Move the hidden rows to the top of the table,
    // just before the first non-hidden row.
    if (visibleRows.length > 0) {
      const firstVisibleRowParent = visibleRows[1]?.parentNode;
      if (firstVisibleRowParent) {
        hiddenRows.forEach((hiddenRow) => {
          firstVisibleRowParent.insertBefore(hiddenRow, visibleRows[1]);
        });
      }
    }
  });
}

/**
 * Move a row in the table by a specified number of rows.
 * @param {number} rowIndex - The index of the row to move.
 * @param {number} moveBy - The number of rows to move the target row (positive or negative).
 * @param {boolean} scrollToRow - Whether to scroll the view to the moved row.
 */
export function moveRow(rowIndex: number, moveBy: number, scrollToRow = false) {
  store.isFormDirty = true;

  const targetRowEl = document.querySelector<HTMLTableRowElement>(
    `tr[data-row-index="${rowIndex}"]`,
  );

  if (targetRowEl) {
    const tbodyEl = targetRowEl.closest('tbody');

    if (tbodyEl) {
      const currentIndex = Array.prototype.indexOf.call(tbodyEl.children, targetRowEl);
      let newIndex = currentIndex + moveBy;

      // If we're moving the first row up or the last row down,
      // the move is redundant and we can bail.
      if (
        // prettier-ignore
        (currentIndex === 0 && moveBy < 0)
        || (currentIndex === tbodyEl.children.length - 1 && moveBy > 1)
      ) {
        return;
      }

      // Get the index of the last hidden row in the table.
      const lastHiddenRowIndex = getLastHiddenRowIndex(tbodyEl);

      // Adjust the new index based on hidden rows and table boundaries.
      newIndex = adjustNewIndex(newIndex, lastHiddenRowIndex, moveBy);

      // Move to the row the new index. We'll also save the current scroll position
      // before the move and restore it immediately after the move. If we don't do this,
      // there is a rare circumstance where the browser will automatically adjust the
      // scroll position, leading to an undesired scroll jump.
      /** @see https://github.com/RetroAchievements/RAWeb/pull/1439#issuecomment-1493380833 */
      const currentScrollPosition = window.scrollY;
      tbodyEl.insertBefore(targetRowEl, tbodyEl.children[newIndex + (moveBy > 0 ? 1 : 0)]);
      window.scrollTo({ top: currentScrollPosition, behavior: 'auto' });

      // Scroll the view to the moved row if scrollToRow is true.
      if (scrollToRow) {
        const scrollPosition = targetRowEl.getBoundingClientRect().top + window.scrollY - 64;
        window.scrollTo({ top: scrollPosition, behavior: 'smooth' });

        if (store.manualMoveTimeoutId) {
          clearTimeout(store.manualMoveTimeoutId);
        }

        setRowOutlineVisibility(targetRowEl, true);

        store.manualMoveTimeoutId = setTimeout(() => {
          setRowOutlineVisibility(targetRowEl, false);
        }, 1500);
      }
    }
  }
}

/**
 * Lifecycle events start here
 */

function onMount() {
  window.addEventListener('beforeunload', function (event) {
    if (store.isFormDirty) {
      event.preventDefault();
      // Most browsers will override this with their own "unsaved changes" message.
      event.returnValue = 'You have unsaved changes. Do you still want to leave?';
    }
  });
}

onMount();
