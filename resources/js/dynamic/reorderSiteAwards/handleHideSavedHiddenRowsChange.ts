import { setCookie } from '@/utils/cookie';

const cookieName = 'prefers_no_saved_hidden_rows_when_reordering';

export function handleHideSavedHiddenRowsChange(event: MouseEvent) {
  const isChecked = (event.target as HTMLInputElement).checked;
  setCookie(cookieName, isChecked ? 'true' : 'false');

  const allSavedHiddenRowEls = document.querySelectorAll<HTMLTableRowElement>('tr.saved-hidden');
  allSavedHiddenRowEls.forEach((rowEl) => {
    if (isChecked) {
      rowEl.classList.add('hidden');
    } else {
      rowEl.classList.remove('hidden');
    }
  });
}
