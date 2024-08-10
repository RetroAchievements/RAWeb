import { showSavedHiddenRowsCookie } from './showSavedHiddenRowsCookie';

export const setSavedHiddenRowsVisiblity = () => {
  const isShowSavedHiddenRowsChecked = showSavedHiddenRowsCookie.get();

  const allSavedHiddenRowEls = document.querySelectorAll<HTMLTableRowElement>('tr.saved-hidden');
  for (const rowEl of allSavedHiddenRowEls) {
    if (isShowSavedHiddenRowsChecked) {
      rowEl.classList.remove('hidden');
    } else {
      rowEl.classList.add('hidden');
    }
  }
};
