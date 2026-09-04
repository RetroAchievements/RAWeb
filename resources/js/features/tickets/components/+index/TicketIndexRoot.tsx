import type { ColumnFiltersState } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { DataTablePaginationControls } from '@/common/components/DataTablePaginationControls';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useTicketListColumnDefinitions } from '../../hooks/useTicketListColumnDefinitions';
import { useTicketListFilterProperties } from '../../hooks/useTicketListFilterProperties';
import { useTicketListTableRoot } from '../../hooks/useTicketListTableRoot';
import { buildTicketListTargetParams } from '../../utils/buildTicketListTargetParams';
import { getActiveTicketListFilterProperties } from '../../utils/getActiveTicketListFilterProperties';
import { getAreTicketListFiltersNonDefault } from '../../utils/getAreTicketListFiltersNonDefault';
import { TicketListDisplayPanel } from '../TicketListDisplayPanel';
import { TicketListFilterChips } from '../TicketListFilterChips';
import { TicketListFilterControl } from '../TicketListFilterControl';
import { TicketListHeading } from '../TicketListHeading';
import { TicketListResetFiltersButton } from '../TicketListResetFiltersButton';
import { TicketListTable } from '../TicketListTable';

export const TicketIndexRoot: FC = () => {
  const {
    achievement,
    availableFilters,
    defaultStatusFilter,
    facetCounts,
    hasStatusFilter,
    game,
    paginatedTickets,
    scope,
    stateCounts,
    user,
  } = usePageProps<App.Platform.Data.TicketListPageProps>();
  const { t } = useTranslation();

  const columnDefinitions = useTicketListColumnDefinitions();

  const serverDefaultColumnFilters: ColumnFiltersState = [
    { id: 'status', value: [defaultStatusFilter] },
    ...availableFilters.map((filter) => ({ id: filter.kind, value: [filter.values[0]] })),
  ];

  const { ticketListTableProps } = useTicketListTableRoot({
    serverDefaultColumnFilters,
    facetCounts,
    paginatedTickets,
    scope,
    stateCounts,
    targetParams: buildTicketListTargetParams({ achievement, game, user }),
  });

  const filterProperties = useTicketListFilterProperties(
    availableFilters,
    ticketListTableProps.stateCounts,
    ticketListTableProps.facetCounts,
    hasStatusFilter,
  );

  const hasFilterChips =
    getActiveTicketListFilterProperties(filterProperties, ticketListTableProps.columnFilters)
      .length > 0;

  const hasNonDefaultFilters = getAreTicketListFiltersNonDefault(
    ticketListTableProps.columnFilters,
    serverDefaultColumnFilters,
  );

  const visibleTotal = ticketListTableProps.paginatedTickets.total;
  const unfilteredTotal = ticketListTableProps.paginatedTickets.unfilteredTotal;

  return (
    <div
      id="pagination-scroll-target"
      data-testid="ticket-list"
      className="flex scroll-mt-16 flex-col gap-4"
    >
      <TicketListHeading />

      <div className="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
        {hasFilterChips ? (
          <div className="flex flex-wrap items-center gap-2 sm:contents">
            <TicketListFilterChips
              columnFilters={ticketListTableProps.columnFilters}
              properties={filterProperties}
              setColumnFilters={ticketListTableProps.setColumnFilters}
            />
          </div>
        ) : null}

        <div className="flex flex-wrap items-center gap-2 sm:contents">
          {filterProperties.length ? (
            <TicketListFilterControl
              columnFilters={ticketListTableProps.columnFilters}
              isLabelHidden={hasFilterChips && hasNonDefaultFilters}
              properties={filterProperties}
              setColumnFilters={ticketListTableProps.setColumnFilters}
            />
          ) : null}

          {hasNonDefaultFilters ? (
            <TicketListResetFiltersButton
              serverDefaultColumnFilters={serverDefaultColumnFilters}
              setColumnFilters={ticketListTableProps.setColumnFilters}
            />
          ) : null}

          <div className="ml-auto flex items-center gap-2">
            {visibleTotal > 0 ? (
              <p className="whitespace-nowrap text-neutral-200 light:text-neutral-900">
                {unfilteredTotal && unfilteredTotal !== visibleTotal
                  ? t('{{visible, number}} of {{total, number}} tickets', {
                      visible: visibleTotal,
                      total: unfilteredTotal,
                      count: unfilteredTotal,
                    })
                  : t('{{val, number}} tickets', { count: visibleTotal, val: visibleTotal })}
              </p>
            ) : null}

            <TicketListDisplayPanel
              columnDefinitions={columnDefinitions}
              columnVisibility={ticketListTableProps.columnVisibility}
              hasColumnVisibilityOverrides={ticketListTableProps.hasColumnVisibilityOverrides}
              onResetDisplay={ticketListTableProps.resetDisplay}
              onSortChange={ticketListTableProps.setSortParam}
              onToggleColumn={ticketListTableProps.toggleColumnVisibility}
              sortParam={ticketListTableProps.sortParam}
            />
          </div>
        </div>
      </div>

      <TicketListTable
        columnDefinitions={columnDefinitions}
        columnVisibility={ticketListTableProps.columnVisibility}
        isFetching={ticketListTableProps.isFetching}
        paginatedTickets={ticketListTableProps.paginatedTickets}
        paginatorNode={
          <div className="flex items-center justify-center sm:justify-end">
            <DataTablePaginationControls
              currentPage={ticketListTableProps.paginatedTickets.currentPage}
              lastPage={ticketListTableProps.paginatedTickets.lastPage}
              onPageChange={ticketListTableProps.setPageNumber}
              onPrefetchPage={ticketListTableProps.prefetchPage}
            />
          </div>
        }
      />
    </div>
  );
};
