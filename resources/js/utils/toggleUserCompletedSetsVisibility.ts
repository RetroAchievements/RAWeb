import { setCookie } from '@/utils/cookie';

export const cookieName = 'prefers_hidden_user_completed_sets';

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
    'hide-user-completed-sets-checkbox',
  ) as HTMLInputElement | null;

  const completionProgressRowEls = document.querySelectorAll<HTMLTableRowElement>(
    '#usercompletedgamescomponent tr.completion-progress-completed-row',
  );

  if (checkboxEl && completionProgressRowEls) {
    const isChecked = checkboxEl.checked;
    setCookie(cookieName, isChecked ? 'true' : 'false');

    for (const rowEl of completionProgressRowEls) {
      if (isChecked) {
        rowEl.classList.add('hidden');
      } else {
        rowEl.classList.remove('hidden');
      }
    }
  }
}
