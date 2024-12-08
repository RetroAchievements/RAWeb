import type { Column } from '@tanstack/react-table';

export function doesColumnExist<TData>(
  allColumns: Column<TData, unknown>[],
  columnId: string,
): boolean {
  return allColumns.some((column) => column.id === columnId);
}
