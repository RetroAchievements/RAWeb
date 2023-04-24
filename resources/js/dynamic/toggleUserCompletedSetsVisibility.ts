/**
 * Toggles the visibility of user completed sets in a table based on the
 * state of the "Hide user completed sets" checkbox. When the checkbox is
 * checked, it calls this function, which hides the table rows for completed
 * or mastered games. If the checkbox is unchecked, the rows reappear.
 *
 * This function assumes the checkbox has an id of 'hide-user-completed-sets-checkbox'
 * and the table rows have the class 'completion-progress-completed-row'.
 */
export function toggleUserCompletedSetsVisibility() {
  const checkboxEl = document.getElementById(
    'hide-user-completed-sets-checkbox'
  ) as HTMLInputElement | null;

  const completionProgressRows = document.querySelectorAll<HTMLTableRowElement>(
    '#usercompletedgamescomponent tr.completion-progress-completed-row'
  );

  if (checkboxEl && completionProgressRows) {
    completionProgressRows.forEach((row) => {
      row.style.display = checkboxEl.checked ? 'none' : '';
    });
  }
}
