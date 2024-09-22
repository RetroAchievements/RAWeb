import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { type FC } from 'react';

import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAutoUpdatingQueryParams } from '../../hooks/useAutoUpdatingQueryParams';
import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { WantToPlayGamesDataTable } from '../WantToPlayGamesDataTable';

export const WantToPlayGamesRoot: FC = () => {
  const { auth, paginatedGameListEntries } =
    usePageProps<App.Community.Data.UserGameListPageProps>();

  const {
    columnFilters,
    columnVisibility,
    pagination,
    setColumnFilters,
    setColumnVisibility,
    setPagination,
    setSorting,
    sorting,
  } = useGameListState(paginatedGameListEntries);

  const { queryClientWithInitialData } = usePreloadedTableDataQueryClient({
    columnFilters,
    pagination,
    sorting,
    paginatedData: paginatedGameListEntries,
  });

  useAutoUpdatingQueryParams({ columnFilters, pagination, sorting });

  if (!auth?.user) {
    return null;
  }

  return (
    <div>
      <div id="pagination-scroll-target" className="scroll-mt-16">
        <UserHeading user={auth.user}>Want to Play Games</UserHeading>
      </div>

      <HydrationBoundary state={dehydrate(queryClientWithInitialData)}>
        <WantToPlayGamesDataTable
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
