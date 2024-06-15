import { setSavedHiddenRowsVisiblity } from './setSavedHiddenRowsVisibility';

export const refreshVisibilityAfterSave = () => {
  const allRowEls = document.querySelectorAll<HTMLTableRowElement>('tr.award-table-row');
  allRowEls.forEach((rowEl) => {
    const isHiddenChecked =
      rowEl.querySelector<HTMLInputElement>('input[type="checkbox"]')?.checked;

    if (isHiddenChecked) {
      rowEl.classList.add('saved-hidden');
    } else {
      rowEl.classList.remove('saved-hidden');
    }
  });

  setSavedHiddenRowsVisiblity();
};
