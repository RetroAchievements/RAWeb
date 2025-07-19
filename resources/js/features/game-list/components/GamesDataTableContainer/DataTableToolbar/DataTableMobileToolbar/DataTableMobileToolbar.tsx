import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';
import { IoLogoGameControllerA } from 'react-icons/io';
import type { RouteName } from 'ziggy-js';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { BaseSkeleton } from '@/common/components/+vendor/BaseSkeleton';

import { useGameListInfiniteQuery } from '../../../../hooks/useGameListInfiniteQuery';
import { DataTableSearchInput } from '../../../DataTableSearchInput';
import { DataTableSuperFilter } from '../DataTableSuperFilter';

/**
 * 🔴 If you make layout updates to this component, you must
 *    also update <DataTableMobileToolbarSuspenseFallback />'s layout.
 *    It's important that the loading skeleton always matches the real
 *    component's layout.
 */

interface DataTableMobileToolbarProps<TData> {
  table: Table<TData>;

  randomGameApiRouteName?: RouteName;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

// Lazy-loaded, so using a default export.
export default function DataTableMobileToolbar<TData>({
  table,
  tableApiRouteParams,
  randomGameApiRouteName = 'api.game.random',
  tableApiRouteName = 'api.game.index',
}: DataTableMobileToolbarProps<TData>) {
  const { t } = useTranslation();

  const tableState = table.getState();

  // Peek into the query to grab the total number of items in the list.
  const infiniteQuery = useGameListInfiniteQuery({
    columnFilters: tableState.columnFilters,
    pagination: tableState.pagination,
    sorting: tableState.sorting,
    apiRouteName: tableApiRouteName,
    apiRouteParams: tableApiRouteParams,
  });

  const totalGames = infiniteQuery.data?.pages[0]?.total ?? 0;

  return (
    <div className="flex w-full flex-col justify-between gap-2 md:flex-row">
      <div className="flex items-center justify-between gap-3 md:justify-normal">
        <BaseChip className="max-h-6 bg-neutral-950 tracking-wide text-neutral-300 light:bg-neutral-200/70 light:text-neutral-950">
          <IoLogoGameControllerA className="mr-0.5 h-6 w-6" />

          {infiniteQuery.isPending ? (
            <BaseSkeleton data-testid="skeleton" className="w-16" />
          ) : (
            t('{{val, number}} Games', { count: totalGames, val: totalGames })
          )}
        </BaseChip>

        <DataTableSuperFilter
          table={table}
          hasResults={totalGames !== 0}
          randomGameApiRouteName={randomGameApiRouteName}
          randomGameApiRouteParams={tableApiRouteParams}
          tableApiRouteName={tableApiRouteName}
        />
      </div>

      <DataTableSearchInput table={table} hasClearButton={true} hasHotkey={false} />
    </div>
  );
}
