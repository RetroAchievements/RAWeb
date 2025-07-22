import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { useAtomValue } from 'jotai';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { useTableSync } from '../../hooks/useTableSync';
import { isCurrentlyPersistingViewAtom } from '../../state/game-list.atoms';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { useAllGamesDefaultColumnState } from './useAllGamesDefaultColumnState';
import { useColumnDefinitions } from './useColumnDefinitions';

export const AllGamesMainRoot: FC = memo(() => {
  const { can, defaultDesktopPageSize, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.GameListPageProps>();

  const { t } = useTranslation();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useAllGamesDefaultColumnState();

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

  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    columnFilters,
    pagination,
    sorting,
    apiRouteName: 'api.game.index',
    apiRouteParams: {},
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
      <DataTablePaginationScrollTarget>
        <div className="mb-3 flex w-full">
          <h1 className="text-h3 w-full sm:!text-[2.0em]">{t('All Games')}</h1>
        </div>
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={dehydrate(queryClientWithInitialData)}>
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
        />
      </HydrationBoundary>
    </div>
  );
});
