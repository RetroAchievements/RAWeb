import { HydrationBoundary } from '@tanstack/react-query';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListTableRoot } from '../../hooks/useGameListTableRoot';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { useColumnDefinitions } from './useColumnDefinitions';
import { useWantToPlayGamesDefaultColumnState } from './useWantToPlayGamesDefaultColumnState';

export const WantToPlayGamesMainRoot: FC = () => {
  const { auth, can, defaultDesktopPageSize, paginatedGameListEntries } =
    usePageProps<App.Community.Data.UserGameListPageProps>();

  const { t } = useTranslation();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useWantToPlayGamesDefaultColumnState();

  const columnDefinitions = useColumnDefinitions({ canSeeOpenTicketsColumn: !!can.develop });

  const { hydrationState, gameListTableProps } = useGameListTableRoot({
    paginatedGameListEntries,
    defaultColumnFilters,
    defaultColumnSort,
    defaultColumnVisibility,
    defaultPageSize: defaultDesktopPageSize,
    apiRouteName: 'api.user-game-list.index',
  });

  if (!auth?.user) {
    return null;
  }

  return (
    <div>
      <DataTablePaginationScrollTarget>
        <UserHeading user={auth.user}>{t('Want to Play Games')}</UserHeading>
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={hydrationState}>
        <GamesDataTableContainer
          {...gameListTableProps}
          columnDefinitions={columnDefinitions}
          randomGameApiRouteName="api.user-game-list.random"
          shouldHideItemIfNotInBacklog={true}
        />
      </HydrationBoundary>
    </div>
  );
};
