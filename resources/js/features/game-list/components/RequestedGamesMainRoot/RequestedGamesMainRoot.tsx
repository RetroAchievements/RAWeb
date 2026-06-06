import { HydrationBoundary } from '@tanstack/react-query';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListTableRoot } from '../../hooks/useGameListTableRoot';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { useColumnDefinitions } from './useColumnDefinitions';
import { useRequestedGamesDefaultColumnState } from './useRequestedGamesDefaultColumnState';
import { UserRequestStatistics } from './UserRequestStatistics/UserRequestStatistics';

export const RequestedGamesMainRoot: FC = () => {
  const { paginatedGameListEntries, targetUser, userRequestInfo } =
    usePageProps<App.Platform.Data.GameListPageProps>();

  const { t } = useTranslation();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useRequestedGamesDefaultColumnState({ targetUser });

  const columnDefinitions = useColumnDefinitions(targetUser);
  const apiRouteName = targetUser ? 'api.set-request.user' : 'api.set-request.index';
  const apiRouteParams = targetUser ? { user: targetUser.displayName } : {};

  const { hydrationState, gameListTableProps } = useGameListTableRoot({
    paginatedGameListEntries,
    defaultColumnFilters,
    defaultColumnSort,
    defaultColumnVisibility,
    defaultPageSize: 50,
    apiRouteName,
    apiRouteParams,
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

      <HydrationBoundary state={hydrationState}>
        <GamesDataTableContainer
          {...gameListTableProps}
          defaultChipOfInterest="numRequests"
          columnDefinitions={columnDefinitions}
          randomGameApiRouteName="api.set-request.random"
        />
      </HydrationBoundary>
    </div>
  );
};
