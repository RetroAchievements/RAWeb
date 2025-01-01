import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { useAtom } from 'jotai';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { useTableSync } from '../../hooks/useTableSync';
import { isCurrentlyPersistingViewAtom } from '../../state/game-list.atoms';
import { allGamesDefaultFilters } from '../../utils/allGamesDefaultFilters';
import { AllGamesDataTable } from '../AllGamesDataTable';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';

export const AllGamesMainRoot: FC = memo(() => {
  const { auth, defaultDesktopPageSize, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.GameListPageProps>();

  const { t } = useTranslation();

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
    canShowProgressColumn: !!auth?.user,
    defaultColumnFilters: allGamesDefaultFilters,
  });

  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    columnFilters,
    pagination,
    sorting,
    paginatedData: paginatedGameListEntries,
  });

  const [isCurrentlyPersistingView] = useAtom(isCurrentlyPersistingViewAtom);

  useTableSync({
    columnFilters,
    columnVisibility,
    pagination,
    sorting,
    defaultFilters: allGamesDefaultFilters,
    defaultPageSize: defaultDesktopPageSize,
    isUserPersistenceEnabled: isCurrentlyPersistingView,
  });

  return (
    <div>
      <DataTablePaginationScrollTarget>
        <div className="mb-3 flex w-full">
          <h1 className="text-h3 w-full sm:!text-[2.0em]">{t('All Games')}</h1>
        </div>
      </DataTablePaginationScrollTarget>

      <HydrationBoundary state={dehydrate(queryClientWithInitialData)}>
        <AllGamesDataTable
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
