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
import { SystemGamesDataTable } from '../SystemGamesDataTable';
import { useSystemGamesDefaultColumnState } from './useSystemGamesDefaultColumnState';

export const SystemGamesMainRoot: FC = memo(() => {
  const { defaultDesktopPageSize, system, paginatedGameListEntries } =
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
            <img src={system.iconUrl} className="-mt-1" />{' '}
            {t('All {{systemName}} Games', { systemName: system.name })}
          </h1>
        </div>
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={dehydrate(queryClientWithInitialData)}>
        <SystemGamesDataTable
          columnFilters={columnFilters}
          columnVisibility={columnVisibility}
          pagination={pagination}
          setColumnFilters={setColumnFilters}
          setColumnVisibility={setColumnVisibility}
          setPagination={setPagination}
          setSorting={setSorting}
          sorting={sorting}
        />
      </HydrationBoundary>
    </div>
  );
});
