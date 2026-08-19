import type { ColumnDef, VisibilityState } from '@tanstack/react-table';
import { flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { type FC, Fragment, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { cn } from '@/common/utils/cn';

import { TicketListEmptyState } from '../TicketListEmptyState';
import { TicketStateGlyph } from '../TicketStateGlyph';

type TicketListTablePage = Pick<
  App.Data.PaginatedData<App.Platform.Data.TicketListEntry>,
  'currentPage' | 'items' | 'lastPage' | 'total'
>;

interface TicketListTableProps {
  columnDefinitions: ColumnDef<App.Platform.Data.TicketListEntry>[];
  columnVisibility: VisibilityState;
  paginatedTickets: TicketListTablePage;

  emptyStateNode?: ReactNode;
}

const glyphSlotClassName = 'mx-[0.6em] flex w-4 flex-none items-center justify-center';

export const TicketListTable: FC<TicketListTableProps> = ({
  columnDefinitions,
  columnVisibility,
  paginatedTickets,
  emptyStateNode,
}) => {
  const { t } = useTranslation();

  const table = useReactTable({
    columns: columnDefinitions,
    data: paginatedTickets.items,
    manualPagination: true,
    rowCount: paginatedTickets.total,
    pageCount: paginatedTickets.lastPage,
    getCoreRowModel: getCoreRowModel(),
    state: {
      columnVisibility,
      pagination: {
        pageIndex: paginatedTickets.currentPage - 1,
        pageSize: paginatedTickets.items.length,
      },
    },
  });

  const rows = table.getRowModel().rows;
  const visibleColumns = table.getVisibleLeafColumns();

  const hasIdColumn = visibleColumns.some((column) => column.id === 'id');

  if (!rows.length) {
    return <>{emptyStateNode ?? <TicketListEmptyState />}</>;
  }

  return (
    <div className="max-w-full overflow-x-auto">
      <div
        role="table"
        className="flex min-w-full flex-col overflow-hidden rounded-[0.3em] bg-embed"
      >
        <div
          role="row"
          className="flex min-w-full items-center gap-[0.6em] p-[0.6em] text-menu-link"
        >
          {hasIdColumn ? null : <span aria-hidden="true" className={glyphSlotClassName} />}

          {visibleColumns.map((column) => (
            <Fragment key={column.id}>
              <div
                role="columnheader"
                className={cn('truncate', column.columnDef.meta?.responsiveClassName)}
              >
                {column.columnDef.meta?.t_label}
              </div>

              {column.id === 'id' ? (
                <span aria-hidden="true" className={glyphSlotClassName} />
              ) : null}
            </Fragment>
          ))}
        </div>

        {rows.map((row) => (
          <div
            key={row.id}
            role="row"
            className="relative flex h-[2.6em] min-w-full items-center gap-[0.6em] px-[0.6em] focus-within:bg-embed-highlight hover:bg-embed-highlight"
          >
            <a
              href={route('ticket.show', { ticket: row.original.id })}
              aria-label={t('Ticket #{{ticketId}}', { ticketId: row.original.id })}
              className="absolute inset-0 rounded-[0.3em] focus-visible:outline-2"
            />

            {hasIdColumn ? null : (
              <div role="cell" className={glyphSlotClassName}>
                <TicketStateGlyph state={row.original.state} />
              </div>
            )}

            {row.getVisibleCells().map((cell) => (
              <Fragment key={cell.id}>
                <div
                  role="cell"
                  className={cn(
                    'min-w-0 overflow-hidden',
                    cell.column.columnDef.meta?.responsiveClassName,
                  )}
                >
                  {flexRender(cell.column.columnDef.cell, cell.getContext())}
                </div>

                {cell.column.id === 'id' ? (
                  <div role="cell" className={glyphSlotClassName}>
                    <TicketStateGlyph state={row.original.state} />
                  </div>
                ) : null}
              </Fragment>
            ))}
          </div>
        ))}
      </div>
    </div>
  );
};
