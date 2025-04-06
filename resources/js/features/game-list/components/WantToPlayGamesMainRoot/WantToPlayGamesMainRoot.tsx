import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { useAtomValue } from 'jotai';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { useTableSync } from '../../hooks/useTableSync';
import { isCurrentlyPersistingViewAtom } from '../../state/game-list.atoms';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { useColumnDefinitions } from './useColumnDefinitions';
import { useWantToPlayGamesDefaultColumnState } from './useWantToPlayGamesDefaultColumnState';

export const WantToPlayGamesMainRoot: FC = memo(() => {
  const { auth, can, defaultDesktopPageSize, paginatedGameListEntries } =
    usePageProps<App.Community.Data.UserGameListPageProps>();

  const { t } = useTranslation();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useWantToPlayGamesDefaultColumnState();

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

  if (!auth?.user) {
    return null;
  }

  return (
    <div>
      <DataTablePaginationScrollTarget>
        <UserHeading user={auth.user}>{t('Want to Play Games')}</UserHeading>
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
          apiRouteName="api.user-game-list.index"
          randomGameApiRouteName="api.user-game-list.random"
          shouldHideItemIfNotInBacklog={true}
        />
      </HydrationBoundary>
    </div>
  );
});
