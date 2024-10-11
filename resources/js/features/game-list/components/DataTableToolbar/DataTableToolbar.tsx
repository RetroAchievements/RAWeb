import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { RouteName } from 'ziggy-js';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';

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

  const { t, tChoice } = useLaravelReactI18n();

  const { formatNumber } = useFormatNumber();

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
            title={t('System')}
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
              {tChoice(':count of :total game|:count of :total games', unfilteredTotal, {
                count: formatNumber(table.options.rowCount ?? 0),
                total: formatNumber(unfilteredTotal),
              })}
            </>
          ) : (
            <>
              {tChoice(':count game|:count games', table.options.rowCount ?? 0, {
                count: formatNumber(table.options.rowCount ?? 0),
              })}
            </>
          )}
        </p>

        <DataTableViewOptions table={table} />
      </div>
    </div>
  );
}
