import { showSavedHiddenRowsCookie } from './showSavedHiddenRowsCookie';

export const setSavedHiddenRowsVisiblity = () => {
  const isShowSavedHiddenRowsChecked = showSavedHiddenRowsCookie.get();

  const allSavedHiddenRowEls = document.querySelectorAll<HTMLTableRowElement>('tr.saved-hidden');
  allSavedHiddenRowEls.forEach((rowEl) => {
    if (isShowSavedHiddenRowsChecked) {
      rowEl.classList.remove('hidden');
    } else {
      rowEl.classList.add('hidden');
    }
  });
};
