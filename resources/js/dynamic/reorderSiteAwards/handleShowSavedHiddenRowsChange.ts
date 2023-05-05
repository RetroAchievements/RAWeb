import { setCookie } from '@/utils/cookie';

const cookieName = 'prefers_seeing_saved_hidden_rows_when_reordering';

/**
 * Toggles the visibility of saved hidden rows in a table based on
 * the user's preference. The preference is then stored in a cookie
 * so it can be persisted across sessions and page refreshes.
 *
 * @param {MouseEvent} event The click event from toggling the checkbox.
 */
export function handleShowSavedHiddenRowsChange(event: MouseEvent) {
  const isChecked = (event.target as HTMLInputElement).checked;
  setCookie(cookieName, isChecked ? 'true' : 'false');

  const allSavedHiddenRowEls = document.querySelectorAll<HTMLTableRowElement>('tr.saved-hidden');
  allSavedHiddenRowEls.forEach((rowEl) => {
    if (isChecked) {
      rowEl.classList.remove('hidden');
    } else {
      rowEl.classList.add('hidden');
    }
  });
}
