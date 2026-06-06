import { HydrationBoundary } from '@tanstack/react-query';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListTableRoot } from '../../hooks/useGameListTableRoot';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { useAllGamesDefaultColumnState } from './useAllGamesDefaultColumnState';
import { useColumnDefinitions } from './useColumnDefinitions';

export const AllGamesMainRoot: FC = () => {
  const { can, defaultDesktopPageSize, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.GameListPageProps>();

  const { t } = useTranslation();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useAllGamesDefaultColumnState();

  const columnDefinitions = useColumnDefinitions({ canSeeOpenTicketsColumn: !!can.develop });

  const { hydrationState, gameListTableProps } = useGameListTableRoot({
    paginatedGameListEntries,
    defaultColumnFilters,
    defaultColumnSort,
    defaultColumnVisibility,
    defaultPageSize: defaultDesktopPageSize,
    apiRouteName: 'api.game.index',
  });

  return (
    <div>
      <DataTablePaginationScrollTarget>
        <div className="mb-3 flex w-full">
          <h1 className="text-h3 w-full sm:!text-[2.0em]">{t('All Games')}</h1>
        </div>
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={hydrationState}>
        <GamesDataTableContainer {...gameListTableProps} columnDefinitions={columnDefinitions} />
      </HydrationBoundary>
    </div>
  );
};
