import type { MappedTableRow } from './index';

/**
 * Collects all award table rows on the page and maps them to MappedTableRow objects.
 */
export function collectMappedTableRows(): MappedTableRow[] {
  const mappedTableRows: MappedTableRow[] = [];
  const awardTableRowEls = document.querySelectorAll<HTMLTableRowElement>('.award-table-row');

  for (const rowEl of awardTableRowEls) {
    const awardType =
      rowEl.querySelector<HTMLInputElement>("input[type='hidden'][name='type']")?.value ?? '';
    const awardKey =
      rowEl.querySelector<HTMLInputElement>("input[type='hidden'][name='data']")?.value ?? '';
    const awardTier =
      rowEl.querySelector<HTMLInputElement>("input[type='hidden'][name='extra']")?.value ?? '';
    const awardKind = rowEl.dataset.awardKind ?? '';
    const isHidden = !!rowEl.querySelector<HTMLInputElement>('input[type="checkbox"]')?.checked;

    mappedTableRows.push({
      isHidden,
      type: awardType,
      data: awardKey,
      extra: awardTier,
      kind: awardKind,
    });
  }

  return mappedTableRows;
}
