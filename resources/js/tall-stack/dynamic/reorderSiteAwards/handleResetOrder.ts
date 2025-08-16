import { collectMappedTableRows } from './collectMappedTableRows';
import type { MappedTableRow } from './index';
import { computeDisplayOrderValues } from './index';

declare global {
  interface Window {
    postAllAwardsDisplayOrder: (awards: Partial<MappedTableRow>[]) => void;
  }
}

export function handleResetOrder(): void {
  if (!confirm('This will reset the order of all awards. Are you sure?')) {
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

  const mappedTableRows = collectMappedTableRows();

  window.postAllAwardsDisplayOrder(computeDisplayOrderValues(mappedTableRows));
}

const sortAwardsByAwardDate = (awards: Array<HTMLTableRowElement>): void => {
  awards.sort((a, b) => {
    const dateA = a.getAttribute('data-award-date') ?? '';
    const dateB = b.getAttribute('data-award-date') ?? '';

    let numA: number;
    let numB: number;

    if (!isNaN(Number(dateA)) && !isNaN(Number(dateB))) {
      numA = parseInt(dateA, 10);
      numB = parseInt(dateB, 10);
    } else {
      numA = Date.parse(dateA) || 0;
      numB = Date.parse(dateB) || 0;
    }

    return numA - numB;
  });
};
