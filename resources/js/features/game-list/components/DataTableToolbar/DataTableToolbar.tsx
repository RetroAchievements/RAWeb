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
  tableApiRouteName?: RouteName;
}

export function DataTableToolbar<TData>({
  table,
  unfilteredTotal,
  defaultColumnFilters = [],
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
          tableApiRouteName={tableApiRouteName}
        />
      </Suspense>
    );
  }

  return (
    <DataTableDesktopToolbar
      table={table}
      unfilteredTotal={unfilteredTotal}
      defaultColumnFilters={defaultColumnFilters}
      tableApiRouteName={tableApiRouteName}
    />
  );
}
