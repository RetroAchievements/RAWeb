import {
  flexRender,
  getCoreRowModel,
  useReactTable,
  type VisibilityState,
} from '@tanstack/react-table';
import { type FC, Fragment, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';
import { cn } from '@/common/utils/cn';

import { useSyncedHorizontalScroll } from '../../hooks/useSyncedHorizontalScroll';
import type { TicketListColumnDefinition } from '../../models';
import { getTicketListGroupHeaders } from '../../utils/getTicketListGroupHeaders';
import { TicketListEmptyState } from '../TicketListEmptyState';
import { TicketSignalVerdict } from '../TicketSignalVerdict';
import { TicketStateGlyph } from '../TicketStateGlyph';
import { TicketListGroupHeaderRow } from './TicketListGroupHeaderRow';
import { TicketListMobileRow } from './TicketListMobileRow';

const glyphSlotClassName = 'mx-[0.6em] flex w-4 flex-none items-center justify-center';

type TicketListTablePage = Pick<
  App.Data.PaginatedData<App.Platform.Data.TicketListEntry>,
  'currentPage' | 'items' | 'lastPage' | 'total'
>;

interface TicketListTableProps {
  columnDefinitions: TicketListColumnDefinition[];
  columnVisibility: VisibilityState;
  hasVerdictSlot: boolean;
  paginatedTickets: TicketListTablePage;

  emptyStateNode?: ReactNode;

  grouping?: App.Platform.Data.TicketListGrouping | null;

  isFetching?: boolean;
  paginatorNode?: ReactNode;
  shouldShowGameTitle?: boolean;

  shouldShowTicketable?: boolean;
}

export const TicketListTable: FC<TicketListTableProps> = ({
  columnDefinitions,
  columnVisibility,
  emptyStateNode,
  grouping,
  hasVerdictSlot,
  paginatedTickets,
  paginatorNode,
  isFetching = false,
  shouldShowGameTitle = true,
  shouldShowTicketable = true,
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
  const hasTicketableColumn = visibleColumns.some((column) => column.id === 'ticketable');
  const stretchSlot = getStretchSlot(hasIdColumn, hasTicketableColumn);
  
  const rowCellCount =
    visibleColumns.length + (hasVerdictSlot ? 2 : 1) + (stretchSlot === 'ticketLabel' ? 1 : 0);

  if (!rows.length) {
    return <>{emptyStateNode ?? <TicketListEmptyState />}</>;
  }

  const groupHeaders = getTicketListGroupHeaders(
    grouping,
    rows.map((row) => row.original),
  );

  return (
    <div className="flex flex-col gap-[0.6em]">
      <div
        role="table"
        aria-busy={isFetching ? true : undefined}
        className={cn(
          'min-w-0 max-w-full rounded-[0.3em] bg-embed text-sm',
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
            {hasIdColumn ? null : (
              <RowLeadingSlotsHeader hasVerdictSlot={hasVerdictSlot} stretchSlot={stretchSlot} />
            )}

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
                  <RowLeadingSlotsHeader
                    hasVerdictSlot={hasVerdictSlot}
                    stretchSlot={stretchSlot}
                  />
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
          {rows.map((row, index) => {
            const groupHeader = groupHeaders[index];

            return (
              <Fragment key={row.id}>
                {grouping && groupHeader ? (
                  <TicketListGroupHeaderRow
                    columnCount={rowCellCount}
                    entry={row.original}
                    field={grouping.field}
                    isContinued={groupHeader.isContinued}
                    ticketCount={groupHeader.ticketCount}
                  />
                ) : null}

                <div
                  role="row"
                  className={cn(
                    'relative flex h-[2.6em] min-w-full items-center gap-[0.6em] px-[0.6em]',
                    'focus-within:bg-embed-highlight hover:bg-embed-highlight sm:w-min',
                    shouldShowGameTitle ? 'max-sm:h-[3.6em]' : null,
                    shouldShowGameTitle && hasTicketableColumn ? 'sm:h-[3.6em]' : null,
                  )}
                >
                  <InertiaLink
                    href={route('ticket.show', { ticket: row.original.id })}
                    aria-label={t('Ticket #{{ticketId}}', { ticketId: row.original.id })}
                    className="absolute inset-0 rounded-[0.3em] focus-visible:outline-2"
                  />

                  <TicketListMobileRow
                    entry={row.original}
                    hasVerdictSlot={hasVerdictSlot}
                    shouldShowGameTitle={shouldShowGameTitle}
                    shouldShowTicketable={shouldShowTicketable}
                  />

                  <div className="max-sm:hidden sm:contents">
                    {hasIdColumn ? null : (
                      <RowLeadingSlots
                        entry={row.original}
                        hasVerdictSlot={hasVerdictSlot}
                        stretchSlot={stretchSlot}
                      />
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
                          <RowLeadingSlots
                            entry={row.original}
                            hasVerdictSlot={hasVerdictSlot}
                            stretchSlot={stretchSlot}
                          />
                        ) : null}
                      </Fragment>
                    ))}
                  </div>
                </div>
              </Fragment>
            );
          })}
        </div>
      </div>

      {paginatedTickets.lastPage > 1 ? paginatorNode : null}
    </div>
  );
};

type StretchSlot = 'none' | 'empty' | 'ticketLabel';

function getStretchSlot(hasIdColumn: boolean, hasTicketableColumn: boolean): StretchSlot {
  if (!hasIdColumn) {
    return 'ticketLabel';
  }

  return hasTicketableColumn ? 'none' : 'empty';
}

const stretchSlotClassName = 'min-w-0 flex-1 truncate';

interface RowLeadingSlotsHeaderProps {
  hasVerdictSlot: boolean;
  stretchSlot: StretchSlot;
}

const RowLeadingSlotsHeader: FC<RowLeadingSlotsHeaderProps> = ({ hasVerdictSlot, stretchSlot }) => {
  const { t } = useTranslation();

  return (
    <>
      <span aria-hidden="true" className={glyphSlotClassName} />
      {hasVerdictSlot ? <span aria-hidden="true" className={glyphSlotClassName} /> : null}

      {stretchSlot === 'empty' ? <span aria-hidden="true" className="flex-1" /> : null}

      {stretchSlot === 'ticketLabel' ? (
        <div role="columnheader" className={stretchSlotClassName}>
          {t('Ticket')}
        </div>
      ) : null}
    </>
  );
};

interface RowLeadingSlotsProps {
  entry: App.Platform.Data.TicketListEntry;
  hasVerdictSlot: boolean;
  stretchSlot: StretchSlot;
}

const RowLeadingSlots: FC<RowLeadingSlotsProps> = ({ entry, hasVerdictSlot, stretchSlot }) => {
  const { t } = useTranslation();

  return (
    <>
      <div role="cell" className={glyphSlotClassName}>
        <TicketStateGlyph state={entry.state} />
      </div>

      {hasVerdictSlot ? (
        <div role="cell" className={glyphSlotClassName}>
          {entry.signals ? (
            <TicketSignalVerdict
              signals={entry.signals}
              reportedAt={entry.createdAt}
              hasTooltip={true}
            />
          ) : null}
        </div>
      ) : null}

      {stretchSlot === 'empty' ? <span aria-hidden="true" className="flex-1" /> : null}
      
      {stretchSlot === 'ticketLabel' ? (
        <div role="cell" className={stretchSlotClassName}>
          <InertiaLink
            href={route('ticket.show', { ticket: entry.id })}
            className="relative z-10 tabular-nums"
          >
            {t('Ticket #{{ticketId}}', { ticketId: entry.id })}
          </InertiaLink>
        </div>
      ) : null}
    </>
  );
};
