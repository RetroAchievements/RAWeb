import { HydrationBoundary } from '@tanstack/react-query';
import type { FC } from 'react';

import { MatureContentWarningDialog } from '@/common/components/MatureContentWarningDialog';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListTableRoot } from '../../hooks/useGameListTableRoot';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { GamesDataTableContainer } from '../GamesDataTableContainer';
import { HubBreadcrumbs } from './HubBreadcrumbs';
import { HubHeading } from './HubHeading';
import { RelatedHubs } from './RelatedHubs';
import { useColumnDefinitions } from './useColumnDefinitions';
import { useHubGamesDefaultColumnState } from './useHubGamesDefaultColumnState';

export const HubMainRoot: FC = () => {
  const { breadcrumbs, can, defaultDesktopPageSize, hub, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.HubPageProps>();

  const { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility } =
    useHubGamesDefaultColumnState();

  const columnDefinitions = useColumnDefinitions({ canSeeOpenTicketsColumn: !!can.develop });
  const apiRouteParams = { gameSet: hub.id };

  const { hydrationState, gameListTableProps } = useGameListTableRoot({
    paginatedGameListEntries,
    defaultColumnFilters,
    defaultColumnSort,
    defaultColumnVisibility,
    defaultPageSize: defaultDesktopPageSize,
    apiRouteName: 'api.hub.game.index',
    apiRouteParams,
  });

  return (
    <div>
      {hub.hasMatureContent ? <MatureContentWarningDialog /> : null}

      <DataTablePaginationScrollTarget>
        <HubBreadcrumbs breadcrumbs={breadcrumbs} />

        <HubHeading />
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={hydrationState}>
        <div className="flex flex-col gap-5">
          {paginatedGameListEntries.unfilteredTotal ? (
            <GamesDataTableContainer
              {...gameListTableProps}
              columnDefinitions={columnDefinitions}
              randomGameApiRouteName="api.hub.game.random"
            />
          ) : null}

          <RelatedHubs />
        </div>
      </HydrationBoundary>
    </div>
  );
};
