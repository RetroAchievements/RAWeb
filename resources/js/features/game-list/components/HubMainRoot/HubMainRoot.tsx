import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { useAtomValue } from 'jotai';
import { type FC, memo } from 'react';

import { MatureContentWarningDialog } from '@/common/components/MatureContentWarningDialog';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { useTableSync } from '../../hooks/useTableSync';
import { isCurrentlyPersistingViewAtom } from '../../state/game-list.atoms';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { HubBreadcrumbs } from './HubBreadcrumbs';
import { HubHeading } from './HubHeading';
import { RelatedHubs } from './RelatedHubs';
import { useColumnDefinitions } from './useColumnDefinitions';
import { useHubGamesDefaultColumnState } from './useHubGamesDefaultColumnState';

export const HubMainRoot: FC = memo(() => {
  const { breadcrumbs, can, defaultDesktopPageSize, hub, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.HubPageProps>();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useHubGamesDefaultColumnState();

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
    defaultColumnSort,
    defaultColumnFilters,
    defaultColumnVisibility,
  });

  const columnDefinitions = useColumnDefinitions({ canSeeOpenTicketsColumn: !!can.develop });

  const apiRouteParams = { gameSet: hub.id };
  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    apiRouteParams,
    columnFilters,
    pagination,
    sorting,
    apiRouteName: 'api.hub.game.index',
    paginatedData: paginatedGameListEntries,
  });

  const isCurrentlyPersistingView = useAtomValue(isCurrentlyPersistingViewAtom);

  useTableSync({
    columnFilters,
    columnVisibility,
    defaultColumnFilters,
    defaultColumnSort,
    pagination,
    sorting,
    defaultPageSize: defaultDesktopPageSize,
    isUserPersistenceEnabled: isCurrentlyPersistingView,
  });

  return (
    <div>
      {hub.hasMatureContent ? <MatureContentWarningDialog /> : null}

      <DataTablePaginationScrollTarget>
        <HubBreadcrumbs breadcrumbs={breadcrumbs} />

        <HubHeading />
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={dehydrate(queryClientWithInitialData)}>
        <div className="flex flex-col gap-5">
          {paginatedGameListEntries.unfilteredTotal ? (
            <GamesDataTableContainer
              // Table state
              columnFilters={columnFilters}
              columnVisibility={columnVisibility}
              pagination={pagination}
              sorting={sorting}
              // State setters
              setColumnFilters={setColumnFilters}
              setColumnVisibility={setColumnVisibility}
              setPagination={setPagination}
              setSorting={setSorting}
              // Table configuration
              defaultColumnFilters={defaultColumnFilters}
              columnDefinitions={columnDefinitions}
              // API configuration
              apiRouteName="api.hub.game.index"
              apiRouteParams={apiRouteParams}
              randomGameApiRouteName="api.hub.game.random"
            />
          ) : null}

          <RelatedHubs />
        </div>
      </HydrationBoundary>
    </div>
  );
});
