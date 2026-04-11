import { HydrationBoundary } from '@tanstack/react-query';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

import { useGameListTableRoot } from '../../hooks/useGameListTableRoot';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { useColumnDefinitions } from './useColumnDefinitions';
import { useSystemGamesDefaultColumnState } from './useSystemGamesDefaultColumnState';

export const SystemGamesMainRoot: FC = () => {
  const { can, defaultDesktopPageSize, system, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const { t } = useTranslation();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useSystemGamesDefaultColumnState();

  const columnDefinitions = useColumnDefinitions({ canSeeOpenTicketsColumn: !!can.develop });

  const { hydrationState, gameListTableProps } = useGameListTableRoot({
    paginatedGameListEntries,
    defaultColumnFilters,
    defaultColumnSort,
    defaultColumnVisibility,
    defaultPageSize: defaultDesktopPageSize,
    apiRouteName: 'api.system.game.index',
    apiRouteParams: { systemId: system.id },
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

      <HydrationBoundary state={hydrationState}>
        <GamesDataTableContainer
          {...gameListTableProps}
          columnDefinitions={columnDefinitions}
          randomGameApiRouteName="api.system.game.random"
        />
      </HydrationBoundary>
    </div>
  );
};
