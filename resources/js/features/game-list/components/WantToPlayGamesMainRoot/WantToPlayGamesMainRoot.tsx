import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC } from 'react';

import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAutoUpdatingQueryParams } from '../../hooks/useAutoUpdatingQueryParams';
import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { wantToPlayGamesDefaultFilters } from '../../utils/wantToPlayGamesDefaultFilters';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { WantToPlayGamesDataTable } from '../WantToPlayGamesDataTable';

export const WantToPlayGamesMainRoot: FC = () => {
  const { auth, paginatedGameListEntries } =
    usePageProps<App.Community.Data.UserGameListPageProps>();

  const { t } = useLaravelReactI18n();

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
    canShowProgressColumn: true,
    defaultColumnFilters: wantToPlayGamesDefaultFilters,
  });

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
      <DataTablePaginationScrollTarget>
        <UserHeading user={auth.user}>{t('Want to Play Games')}</UserHeading>
      </DataTablePaginationScrollTarget>

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
