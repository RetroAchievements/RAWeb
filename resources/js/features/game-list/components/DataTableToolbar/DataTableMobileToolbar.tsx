import type { Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { IoLogoGameControllerA } from 'react-icons/io';
import type { RouteName } from 'ziggy-js';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { BaseSkeleton } from '@/common/components/+vendor/BaseSkeleton';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';

import { useGameListInfiniteQuery } from '../../hooks/useGameListInfiniteQuery';
import { DataTableSearchInput } from '../DataTableSearchInput';
import { DataTableSuperFilter } from './DataTableSuperFilter';

/**
 * 🔴 If you make layout updates to this component, you must
 *    also update <DataTableMobileToolbarSuspenseFallback />'s layout.
 *    It's important that the loading skeleton always matches the real
 *    component's layout.
 */

interface DataTableMobileToolbarProps<TData> {
  table: Table<TData>;

  tableApiRouteName?: RouteName;
}

// Lazy-loaded, so using a default export.
export default function DataTableMobileToolbar<TData>({
  table,
  tableApiRouteName = 'api.game.index',
}: DataTableMobileToolbarProps<TData>) {
  const { tChoice } = useLaravelReactI18n();

  const { formatNumber } = useFormatNumber();

  const tableState = table.getState();

  // Peek into the query to grab the total number of items in the list.
  const infiniteQuery = useGameListInfiniteQuery({
    columnFilters: tableState.columnFilters,
    pagination: tableState.pagination,
    sorting: tableState.sorting,
    apiRouteName: tableApiRouteName,
  });

  const totalGames = infiniteQuery.data?.pages[0]?.total ?? 0;

  return (
    <div className="flex w-full flex-col justify-between gap-2 md:flex-row">
      <div className="flex items-center justify-between gap-3 md:justify-normal">
        <BaseChip className="max-h-6 bg-neutral-950 tracking-wide text-neutral-300 light:bg-neutral-200/70 light:text-neutral-950">
          <IoLogoGameControllerA className="mr-0.5 h-6 w-6" />

          {infiniteQuery.isPending ? (
            <BaseSkeleton className="w-16" />
          ) : (
            tChoice(':count Game|:count Games', totalGames, {
              count: formatNumber(totalGames),
            })
          )}
        </BaseChip>

        <DataTableSuperFilter table={table} />
      </div>

      <DataTableSearchInput table={table} hasClearButton={true} hasHotkey={false} />
    </div>
  );
}
