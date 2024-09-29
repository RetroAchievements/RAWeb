import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { type FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { useAutoUpdatingQueryParams } from '../../hooks/useAutoUpdatingQueryParams';
import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { allGamesDefaultFilters } from '../../utils/allGamesDefaultFilters';
import { AllGamesDataTable } from '../AllGamesDataTable';

export const AllGamesMainRoot: FC = () => {
  const { paginatedGameListEntries } = usePageProps<App.Platform.Data.GameListPageProps>();

  const {
    columnFilters,
    columnVisibility,
    pagination,
    setColumnFilters,
    setColumnVisibility,
    setPagination,
    setSorting,
    sorting,
  } = useGameListState(paginatedGameListEntries, { defaultColumnFilters: allGamesDefaultFilters });

  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    columnFilters,
    pagination,
    sorting,
    paginatedData: paginatedGameListEntries,
  });

  useAutoUpdatingQueryParams({ columnFilters, pagination, sorting });

  return (
    <div>
      {/* TODO reusable component */}
      <div id="pagination-scroll-target" className="scroll-mt-16">
        <div className="mb-3 flex w-full">
          <h1 className="text-h3 w-full sm:!text-[2.0em]">All Games</h1>
        </div>
      </div>

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
};
