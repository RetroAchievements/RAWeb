import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { useAutoUpdatingQueryParams } from '../../hooks/useAutoUpdatingQueryParams';
import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { useSystemGamesDefaultFilters } from '../../hooks/useSystemGamesDefaultFilters';
import { AllSystemGamesDataTable } from '../AllSystemGamesDataTable';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';

export const SystemGamesMainRoot: FC = () => {
  const { auth, defaultDesktopPageSize, system, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const { t } = useTranslation();

  const { systemGamesDefaultFilters } = useSystemGamesDefaultFilters();

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
    alwaysShowPlayersTotal: true,
    canShowProgressColumn: !!auth?.user,
    defaultColumnFilters: systemGamesDefaultFilters,
  });

  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    columnFilters,
    pagination,
    sorting,
    paginatedData: paginatedGameListEntries,
  });

  useAutoUpdatingQueryParams({
    columnFilters,
    pagination,
    sorting,
    defaultFilters: systemGamesDefaultFilters,
    defaultPageSize: defaultDesktopPageSize,
  });

  return (
    <div>
      <DataTablePaginationScrollTarget>
        <div className="mb-3 flex w-full items-center">
          <h1 className="text-h3 w-full sm:!text-[2.0em]">
            <img src={system.iconUrl} className="-mt-1" />{' '}
            {t('All {{systemName}} Games', { systemName: system.name })}
          </h1>
        </div>
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={dehydrate(queryClientWithInitialData)}>
        <AllSystemGamesDataTable
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
};
