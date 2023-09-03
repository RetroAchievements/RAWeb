function toggleUnlockedRows() {
  const allUnlockedRows = document.querySelectorAll<HTMLLIElement>('li.unlocked-row');

  allUnlockedRows.forEach((rowEl) => {
    if (rowEl.classList.contains('hidden')) {
      rowEl.classList.remove('hidden');
    } else {
      rowEl.classList.add('hidden');
    }
  });
}

export function hideEarnedCheckboxComponent() {
  return { toggleUnlockedRows };
}
