import { dehydrate, HydrationBoundary } from '@tanstack/react-query';
import { useAtom } from 'jotai';
import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListState } from '../../hooks/useGameListState';
import { usePreloadedTableDataQueryClient } from '../../hooks/usePreloadedTableDataQueryClient';
import { useTableSync } from '../../hooks/useTableSync';
import { isCurrentlyPersistingViewAtom } from '../../state/game-list.atoms';
import { wantToPlayGamesDefaultFilters } from '../../utils/wantToPlayGamesDefaultFilters';
import { DataTablePaginationScrollTarget } from '../DataTablePaginationScrollTarget';
import { WantToPlayGamesDataTable } from '../WantToPlayGamesDataTable';

export const WantToPlayGamesMainRoot: FC = () => {
  const { auth, defaultDesktopPageSize, paginatedGameListEntries } =
    usePageProps<App.Community.Data.UserGameListPageProps>();

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
    canShowProgressColumn: true,
    defaultColumnFilters: wantToPlayGamesDefaultFilters,
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
    defaultFilters: wantToPlayGamesDefaultFilters,
    defaultPageSize: defaultDesktopPageSize,
    isUserPersistenceEnabled: isCurrentlyPersistingView,
  });

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
