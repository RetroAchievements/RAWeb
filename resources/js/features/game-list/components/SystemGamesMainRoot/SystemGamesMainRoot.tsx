import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { useAtomValue } from 'jotai';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { useTableSync } from '../../hooks/useTableSync';
import { isCurrentlyPersistingViewAtom } from '../../state/game-list.atoms';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { useColumnDefinitions } from './useColumnDefinitions';
import { useSystemGamesDefaultColumnState } from './useSystemGamesDefaultColumnState';

export const SystemGamesMainRoot: FC = memo(() => {
  const { can, defaultDesktopPageSize, system, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const { t } = useTranslation();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useSystemGamesDefaultColumnState();

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
      <GameBreadcrumbs t_currentPageLabel={system.name as TranslatedString} />

      <DataTablePaginationScrollTarget>
        <div className="mb-3 flex w-full items-center">
          <h1 className="text-h3 w-full sm:!text-[2.0em]">
            <img src={system.iconUrl} alt={system.name} className="-mt-1" />{' '}
            {t('All {{systemName}} Games', { systemName: system.name })}
          </h1>
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
          // API configuration
          apiRouteName="api.system.game.index"
          apiRouteParams={{ systemId: system.id }}
          randomGameApiRouteName="api.system.game.random"
        />
      </HydrationBoundary>
    </div>
  );
});
