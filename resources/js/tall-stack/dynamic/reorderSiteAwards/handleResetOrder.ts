export function handleResetOrder(): void {
  if (
    !confirm(
      'This will resort all your awards by the date they were earned (oldest first). You can preview the changes before saving.',
    )
  ) {
    return;
  }

  const rows = Array.from(document.querySelectorAll<HTMLTableRowElement>('.award-table-row'));

  // Sort rows by date of aquisition (ascending)
  sortAwardsByAwardDate(rows);

  for (const row of rows) {
    const awardKind = row.getAttribute('data-award-kind');
    document
      .querySelector<HTMLTableElement>(`#${awardKind}-reorder-table`)
      ?.querySelector('tbody')
      ?.appendChild(row);
  }
}

const sortAwardsByAwardDate = (awards: Array<HTMLTableRowElement>): void => {
  awards.sort((a, b) => {
    const dateA = a.getAttribute('data-award-date') ?? '0';
    const dateB = b.getAttribute('data-award-date') ?? '0';

    const numA = parseInt(dateA, 10);
    const numB = parseInt(dateB, 10);

    return numA - numB;
  });
};
