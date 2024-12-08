import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import { lazy, Suspense } from 'react';
import type { RouteName } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';

import { DataTableDesktopToolbar } from './DataTableDesktopToolbar';
import { DataTableMobileToolbarSuspenseFallback } from './DataTableMobileToolbarSuspenseFallback';

const DataTableMobileToolbar = lazy(() => import('./DataTableMobileToolbar'));

interface DataTableToolbarProps<TData> {
  table: Table<TData>;
  unfilteredTotal: number | null;

  defaultColumnFilters?: ColumnFiltersState;
  randomGameApiRouteName?: RouteName;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function DataTableToolbar<TData>({
  table,
  tableApiRouteParams,
  unfilteredTotal,
  defaultColumnFilters = [],
  randomGameApiRouteName = 'api.game.random',
  tableApiRouteName = 'api.game.index',
}: DataTableToolbarProps<TData>) {
  const { ziggy } = usePageProps<{
    filterableSystemOptions: App.Platform.Data.System[];
  }>();

  if (ziggy.device === 'mobile') {
    return (
      <Suspense fallback={<DataTableMobileToolbarSuspenseFallback />}>
        <DataTableMobileToolbar
          table={table as Table<unknown>}
          randomGameApiRouteName={randomGameApiRouteName}
          tableApiRouteName={tableApiRouteName}
          tableApiRouteParams={tableApiRouteParams}
        />
      </Suspense>
    );
  }

  return (
    <DataTableDesktopToolbar
      table={table}
      unfilteredTotal={unfilteredTotal}
      defaultColumnFilters={defaultColumnFilters}
      randomGameApiRouteName={randomGameApiRouteName}
      tableApiRouteName={tableApiRouteName}
      tableApiRouteParams={tableApiRouteParams}
    />
  );
}
