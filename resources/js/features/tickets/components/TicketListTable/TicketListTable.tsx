import {
  flexRender,
  getCoreRowModel,
  useReactTable,
  type VisibilityState,
} from '@tanstack/react-table';
import { type FC, Fragment, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { cn } from '@/common/utils/cn';

import { useSyncedHorizontalScroll } from '../../hooks/useSyncedHorizontalScroll';
import type { TicketListColumnDefinition } from '../../models';
import { TicketListEmptyState } from '../TicketListEmptyState';
import { TicketStateGlyph } from '../TicketStateGlyph';
import { TicketListMobileRow } from './TicketListMobileRow';

const glyphSlotClassName = 'mx-[0.6em] flex w-4 flex-none items-center justify-center';

type TicketListTablePage = Pick<
  App.Data.PaginatedData<App.Platform.Data.TicketListEntry>,
  'currentPage' | 'items' | 'lastPage' | 'total'
>;

interface TicketListTableProps {
  columnDefinitions: TicketListColumnDefinition[];
  columnVisibility: VisibilityState;
  paginatedTickets: TicketListTablePage;

  emptyStateNode?: ReactNode;
  isFetching?: boolean;
  paginatorNode?: ReactNode;
}

export const TicketListTable: FC<TicketListTableProps> = ({
  columnDefinitions,
  columnVisibility,
  paginatedTickets,
  emptyStateNode,
  paginatorNode,
  isFetching = false,
}) => {
  const { t } = useTranslation();

  /**
   * The header and row elements use separate horizontal scroll containers
   * so the header can stay sticky while other things scroll around on desktop.
   * The browser doesn't sync scroll positions of the containers automatically,
   * so we do it ourselves with a little bit of JS.
   */
  const { headerElRef, bodyElRef, handleHeaderScroll, handleBodyScroll } =
    useSyncedHorizontalScroll();

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
    <div className="flex flex-col gap-[0.6em]">
      <div
        role="table"
        aria-busy={isFetching ? true : undefined}
        className={cn(
          'min-w-0 max-w-full rounded-[0.3em] bg-embed',
          isFetching ? 'opacity-50' : null,
        )}
      >
        <div
          ref={headerElRef}
          className={cn(
            'scrollbar-none overflow-x-auto rounded-t-[0.3em] bg-embed',
            'max-sm:hidden lg:sticky lg:top-10.25 lg:z-20 [&::-webkit-scrollbar]:hidden',
          )}
          onScroll={handleHeaderScroll}
        >
          <div
            role="row"
            className="flex w-min min-w-full items-center gap-[0.6em] p-[0.6em] text-menu-link"
          >
            {hasIdColumn ? null : <span aria-hidden="true" className={glyphSlotClassName} />}

            {visibleColumns.map((column) => (
              <Fragment key={column.id}>
                <div
                  role="columnheader"
                  className={cn(
                    'truncate contain-[inline-size]',
                    column.columnDef.meta?.responsiveClassName,
                  )}
                >
                  {column.columnDef.meta?.t_label}
                </div>

                {column.id === 'id' ? (
                  <span aria-hidden="true" className={glyphSlotClassName} />
                ) : null}
              </Fragment>
            ))}
          </div>
        </div>

        <div
          ref={bodyElRef}
          className="overflow-x-auto rounded-b-[0.3em]"
          onScroll={handleBodyScroll}
        >
          {rows.map((row) => (
            <div
              key={row.id}
              role="row"
              className={cn(
                'relative flex h-[2.6em] min-w-full items-center gap-[0.6em] px-[0.6em]',
                'focus-within:bg-embed-highlight hover:bg-embed-highlight sm:w-min',
              )}
            >
              <a
                href={route('ticket.show', { ticket: row.original.id })}
                aria-label={t('Ticket #{{ticketId}}', { ticketId: row.original.id })}
                className="absolute inset-0 rounded-[0.3em] focus-visible:outline-2"
              />

              <TicketListMobileRow entry={row.original} />

              <div className="max-sm:hidden sm:contents">
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
                        'min-w-0 overflow-hidden contain-[inline-size]',
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
            </div>
          ))}
        </div>
      </div>

      {paginatedTickets.lastPage > 1 ? paginatorNode : null}
    </div>
  );
};
