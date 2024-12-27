import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import type { FC } from 'react';

import { ContentWarningDialog } from '@/common/components/ContentWarningDialog';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAutoUpdatingQueryParams } from '../../hooks/useAutoUpdatingQueryParams';
import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { hubGamesDefaultFilters } from '../../utils/hubGamesDefaultFilters';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { HubGamesDataTable } from '../HubGamesDataTable';
import { HubBreadcrumbs } from './HubBreadcrumbs';
import { HubHeading } from './HubHeading';
import { RelatedHubs } from './RelatedHubs';

export const HubMainRoot: FC = () => {
  const { auth, breadcrumbs, defaultDesktopPageSize, hub, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.HubPageProps>();

  const {
    columnFilters,
    columnVisibility,
    pagination,
    setColumnFilters,
    setColumnVisibility,
    setPagination,
    setSorting,
    sorting,
  } = useGameListState(paginatedGameListEntries, {
    canShowProgressColumn: !!auth?.user,
    defaultColumnFilters: hubGamesDefaultFilters,
  });

  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    columnFilters,
    pagination,
    sorting,
    paginatedData: paginatedGameListEntries,
  });

  useAutoUpdatingQueryParams({
    columnFilters,
    pagination,
    sorting,
    defaultFilters: hubGamesDefaultFilters,
    defaultPageSize: defaultDesktopPageSize,
  });

  return (
    <div>
      {hub.hasMatureContent ? <ContentWarningDialog /> : null}

      <DataTablePaginationScrollTarget>
        <HubBreadcrumbs breadcrumbs={breadcrumbs} />

        <HubHeading />
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={dehydrate(queryClientWithInitialData)}>
        <div className="flex flex-col gap-5">
          {paginatedGameListEntries.unfilteredTotal ? (
            <HubGamesDataTable
              columnFilters={columnFilters}
              columnVisibility={columnVisibility}
              pagination={pagination}
              setColumnFilters={setColumnFilters}
              setColumnVisibility={setColumnVisibility}
              setPagination={setPagination}
              setSorting={setSorting}
              sorting={sorting}
            />
          ) : null}

          <RelatedHubs />
        </div>
      </HydrationBoundary>
    </div>
  );
};
