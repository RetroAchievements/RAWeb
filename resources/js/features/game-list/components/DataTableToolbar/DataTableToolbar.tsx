import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { getAreNonDefaultFiltersSet } from '../../utils/getAreNonDefaultFiltersSet';
import { DataTableAchievementsPublishedFilter } from '../DataTableAchievementsPublishedFilter';
import { DataTableFacetedFilter } from '../DataTableFacetedFilter';
import { DataTableResetFiltersButton } from '../DataTableResetFiltersButton';
import { DataTableSearchInput } from '../DataTableSearchInput';
import { DataTableViewOptions } from '../DataTableViewOptions';

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
  const { filterableSystemOptions } = usePageProps<{
    filterableSystemOptions: App.Platform.Data.System[];
  }>();

  const currentFilters = table.getState().columnFilters;
  const isFiltered = getAreNonDefaultFiltersSet(currentFilters, defaultColumnFilters);

  return (
    <div className="flex w-full flex-col justify-between gap-2 md:flex-row">
      <div className="flex w-full flex-col items-center gap-2 sm:flex-1 sm:flex-row">
        <DataTableSearchInput table={table} />

        {table.getColumn('system') ? (
          <DataTableFacetedFilter
            className="w-full sm:w-auto"
            column={table.getColumn('system')}
            title="System"
            options={filterableSystemOptions
              .sort((a, b) => a.name.localeCompare(b.name))
              .map((system) => ({
                label: system.name,
                selectedLabel: system.nameShort,
                value: String(system.id),
              }))}
          />
        ) : null}

        {table.getColumn('achievementsPublished') ? (
          <DataTableAchievementsPublishedFilter table={table} />
        ) : null}

        {isFiltered ? (
          <DataTableResetFiltersButton
            table={table}
            defaultColumnFilters={defaultColumnFilters}
            tableApiRouteName={tableApiRouteName}
          />
        ) : null}
      </div>

      <div className="flex items-center justify-between gap-3 md:justify-normal">
        <p className="text-neutral-200 light:text-neutral-900">
          {unfilteredTotal && unfilteredTotal !== table.options.rowCount ? (
            <>
              {formatNumber(table.options.rowCount ?? 0)} of {formatNumber(unfilteredTotal)}{' '}
              {unfilteredTotal === 1 ? 'game' : 'games'}
            </>
          ) : (
            <>
              {formatNumber(table.options.rowCount ?? 0)}{' '}
              {table.options.rowCount === 1 ? 'game' : 'games'}
            </>
          )}
        </p>

        <DataTableViewOptions table={table} />
      </div>
    </div>
  );
}
