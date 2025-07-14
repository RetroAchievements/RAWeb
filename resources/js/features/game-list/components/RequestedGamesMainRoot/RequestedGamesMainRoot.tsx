import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { useAtomValue } from 'jotai';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { useTableSync } from '../../hooks/useTableSync';
import { isCurrentlyPersistingViewAtom } from '../../state/game-list.atoms';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { useColumnDefinitions } from './useColumnDefinitions';
import { useRequestedGamesDefaultColumnState } from './useRequestedGamesDefaultColumnState';
import { UserRequestStatistics } from './UserRequestStatistics/UserRequestStatistics';

export const RequestedGamesMainRoot: FC = memo(() => {
  const { paginatedGameListEntries, targetUser, userRequestInfo } =
    usePageProps<App.Platform.Data.GameListPageProps>();

  const { t } = useTranslation();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useRequestedGamesDefaultColumnState({ targetUser });

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

  const columnDefinitions = useColumnDefinitions();

  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    columnFilters,
    pagination,
    sorting,
    paginatedData: paginatedGameListEntries,
    apiRouteName: targetUser ? 'api.set-request.user' : 'api.set-request.index',
    apiRouteParams: targetUser ? { user: targetUser.displayName } : {},
  });

  const isCurrentlyPersistingView = useAtomValue(isCurrentlyPersistingViewAtom);

  useTableSync({
    columnFilters,
    columnVisibility,
    defaultColumnFilters,
    defaultColumnSort,
    pagination,
    sorting,
    defaultPageSize: 50,
    isUserPersistenceEnabled: isCurrentlyPersistingView,
  });

  return (
    <div>
      <DataTablePaginationScrollTarget>
        <div className="mb-3 flex w-full">
          {targetUser ? (
            <div className="flex w-full flex-col">
              <UserBreadcrumbs user={targetUser} t_currentPageLabel={t('Set Requests')} />
              <UserHeading user={targetUser} wrapperClassName="!mb-1">
                {t('Set Requests')}
              </UserHeading>
            </div>
          ) : (
            <h1 className="text-h3 w-full sm:!text-[2.0em]">{t('Most Requested Sets')}</h1>
          )}
        </div>
      </DataTablePaginationScrollTarget>

      {targetUser && userRequestInfo ? (
        <div className="mb-4">
          <UserRequestStatistics targetUser={targetUser} userRequestInfo={userRequestInfo} />
        </div>
      ) : null}

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
          apiRouteName={targetUser ? 'api.set-request.user' : 'api.set-request.index'}
          apiRouteParams={targetUser ? { user: targetUser.displayName } : {}}
          randomGameApiRouteName="api.set-request.random"
        />
      </HydrationBoundary>
    </div>
  );
});
