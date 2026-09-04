import type { ColumnFiltersState } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { DataTablePaginationControls } from '@/common/components/DataTablePaginationControls';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useTicketListColumnDefinitions } from '../../hooks/useTicketListColumnDefinitions';
import { useTicketListFilterProperties } from '../../hooks/useTicketListFilterProperties';
import { useTicketListTableRoot } from '../../hooks/useTicketListTableRoot';
import { getActiveTicketListFilterProperties } from '../../utils/getActiveTicketListFilterProperties';
import { getAreTicketListFiltersNonDefault } from '../../utils/getAreTicketListFiltersNonDefault';
import { TicketListDisplayPanel } from '../TicketListDisplayPanel';
import { TicketListFilterChips } from '../TicketListFilterChips';
import { TicketListFilterControl } from '../TicketListFilterControl';
import { TicketListHeading } from '../TicketListHeading';
import { TicketListResetFiltersButton } from '../TicketListResetFiltersButton';
import { TicketListTable } from '../TicketListTable';

const SERVER_DEFAULT_STATUS_FILTER: ColumnFiltersState = [{ id: 'status', value: ['unresolved'] }];

export const TicketIndexRoot: FC = () => {
  const { availableFilters, facetCounts, paginatedTickets, scope, stateCounts } =
    usePageProps<App.Platform.Data.TicketListPageProps>();
  const { t } = useTranslation();

  const columnDefinitions = useTicketListColumnDefinitions();

  const serverDefaultColumnFilters: ColumnFiltersState = [
    ...SERVER_DEFAULT_STATUS_FILTER,
    ...availableFilters.map((filter) => ({ id: filter.kind, value: [filter.values[0]] })),
  ];

  const { ticketListTableProps } = useTicketListTableRoot({
    serverDefaultColumnFilters,
    facetCounts,
    paginatedTickets,
    scope,
    stateCounts,
  });

  const filterProperties = useTicketListFilterProperties(
    availableFilters,
    ticketListTableProps.stateCounts,
    ticketListTableProps.facetCounts,
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

      <div className="flex w-full flex-wrap items-center gap-2">
        <TicketListFilterChips
          columnFilters={ticketListTableProps.columnFilters}
          properties={filterProperties}
          setColumnFilters={ticketListTableProps.setColumnFilters}
        />

        <TicketListFilterControl
          columnFilters={ticketListTableProps.columnFilters}
          isLabelHidden={hasFilterChips && hasNonDefaultFilters}
          properties={filterProperties}
          setColumnFilters={ticketListTableProps.setColumnFilters}
        />

        {hasNonDefaultFilters ? (
          <TicketListResetFiltersButton
            serverDefaultColumnFilters={serverDefaultColumnFilters}
            setColumnFilters={ticketListTableProps.setColumnFilters}
          />
        ) : null}

        <p className="ml-auto text-neutral-200 light:text-neutral-900">
          {unfilteredTotal && unfilteredTotal !== visibleTotal
            ? t('{{visible, number}} of {{total, number}} tickets', {
                visible: visibleTotal,
                total: unfilteredTotal,
                count: unfilteredTotal,
              })
            : t('{{val, number}} tickets', { count: visibleTotal, val: visibleTotal })}
        </p>

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
