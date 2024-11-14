import type { Table } from '@tanstack/react-table';
import { flexRender } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { cn } from '@/utils/cn';

interface GameListDataTableProps<TData> {
  table: Table<TData>;
}

// Lazy-loaded, so using a default export.
export default function GameListDataTable<TData>({ table }: GameListDataTableProps<TData>) {
  const { t } = useTranslation();

  const visibleColumnCount = table.getVisibleFlatColumns().length;

  return (
    <BaseTable
      containerClassName={cn(
        'overflow-auto rounded-md border border-neutral-700/80 bg-embed',
        'light:border-neutral-300 lg:overflow-visible lg:rounded-sm',

        // A sticky header cannot support this many columns. We have to drop stickiness.
        visibleColumnCount > 8 ? 'lg:!overflow-x-scroll' : '',
        visibleColumnCount > 10 ? 'xl:!overflow-x-scroll' : '',
      )}
    >
      <BaseTableHeader>
        {table.getHeaderGroups().map((headerGroup) => (
          <BaseTableRow
            key={headerGroup.id}
            className={cn(
              'do-not-highlight bg-embed lg:sticky lg:top-[41px] lg:z-10',

              // A sticky header cannot support this many columns. We have to drop stickiness.
              visibleColumnCount > 8 ? 'lg:!top-0' : '',
              visibleColumnCount > 10 ? 'xl:!top-0' : '',
            )}
          >
            {headerGroup.headers.map((header) => {
              return (
                <BaseTableHead key={header.id}>
                  {flexRender(header.column.columnDef.header, header.getContext())}
                </BaseTableHead>
              );
            })}
          </BaseTableRow>
        ))}
      </BaseTableHeader>

      <BaseTableBody>
        {table.getRowModel().rows?.length ? (
          table.getRowModel().rows.map((row) => (
            <BaseTableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
              {row.getVisibleCells().map((cell) => (
                <BaseTableCell
                  key={cell.id}
                  className={cn(
                    cell.column.columnDef.meta?.align === 'right' ? 'pr-6 text-right' : '',
                  )}
                >
                  {flexRender(cell.column.columnDef.cell, cell.getContext())}
                </BaseTableCell>
              ))}
            </BaseTableRow>
          ))
        ) : (
          <BaseTableRow>
            <BaseTableCell
              colSpan={table.getAllColumns().length}
              className="h-24 bg-embed text-center"
            >
              {t('No results.')}
            </BaseTableCell>
          </BaseTableRow>
        )}
      </BaseTableBody>
    </BaseTable>
  );
}
