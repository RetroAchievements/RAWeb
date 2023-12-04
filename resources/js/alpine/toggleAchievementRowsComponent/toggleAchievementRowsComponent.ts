function toggleUnlockedRows() {
  const allFoundRows = document.querySelectorAll<HTMLLIElement>('li.unlocked-row');

  allFoundRows.forEach((rowEl) => {
    if (rowEl.classList.contains('hidden')) {
      rowEl.classList.remove('hidden');
    } else {
      rowEl.classList.add('hidden');
    }
  });
}

function toggleNonMissableRows(): void {
  const allRows = document.querySelectorAll<HTMLLIElement>('#set-achievements-list > li');

  allRows.forEach((row) => {
    if (!row.classList.contains('missable-row')) {
      row.classList.toggle('hidden');
    }
  });
}

export function toggleAchievementRowsComponent() {
  return { toggleUnlockedRows, toggleNonMissableRows };
}
