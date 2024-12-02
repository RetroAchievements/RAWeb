import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';
import type { RouteName } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';

import { getAreNonDefaultFiltersSet } from '../../utils/getAreNonDefaultFiltersSet';
import { DataTableResetFiltersButton } from '../DataTableResetFiltersButton';
import { DataTableSearchInput } from '../DataTableSearchInput';
import { DataTableViewOptions } from '../DataTableViewOptions';
import { DataTableAchievementsPublishedFilter } from './DataTableAchievementsPublishedFilter';
import { DataTableProgressFilter } from './DataTableProgressFilter';
import { DataTableSystemFilter } from './DataTableSystemFilter';
import { RandomGameButton } from './RandomGameButton';

interface DataTableDesktopToolbarProps<TData> {
  table: Table<TData>;
  unfilteredTotal: number | null;

  defaultColumnFilters?: ColumnFiltersState;
  randomGameApiRouteName?: RouteName;
  tableApiRouteName?: RouteName;
}

export function DataTableDesktopToolbar<TData>({
  table,
  unfilteredTotal,
  defaultColumnFilters = [],
  randomGameApiRouteName = 'api.game.random',
  tableApiRouteName = 'api.game.index',
}: DataTableDesktopToolbarProps<TData>) {
  const { filterableSystemOptions } = usePageProps<{
    filterableSystemOptions: App.Platform.Data.System[];
  }>();

  const { t } = useTranslation();

  const currentFilters = table.getState().columnFilters;
  const isFiltered = getAreNonDefaultFiltersSet(currentFilters, defaultColumnFilters);

  return (
    <div className="flex w-full flex-col justify-between gap-2">
      <div className="flex flex-col gap-2 rounded bg-embed p-2 sm:flex-row sm:gap-2 md:gap-3">
        {table.getColumn('system') && filterableSystemOptions ? (
          <DataTableSystemFilter table={table} filterableSystemOptions={filterableSystemOptions} />
        ) : null}

        {table.getColumn('achievementsPublished') ? (
          <DataTableAchievementsPublishedFilter table={table} />
        ) : null}

        {table.getColumn('progress') ? <DataTableProgressFilter table={table} /> : null}

        {isFiltered ? (
          <DataTableResetFiltersButton
            table={table}
            defaultColumnFilters={defaultColumnFilters}
            tableApiRouteName={tableApiRouteName}
          />
        ) : null}
      </div>

      <div className="flex w-full flex-col justify-between gap-2 sm:flex-row">
        <div className="flex w-full flex-col items-center gap-2 sm:flex sm:flex-1 sm:flex-row">
          <DataTableSearchInput table={table} />
        </div>

        <div className="flex items-center justify-between gap-3 md:justify-normal">
          <p className="mr-2 text-neutral-200 light:text-neutral-900">
            {unfilteredTotal && unfilteredTotal !== table.options.rowCount ? (
              <>
                {t('{{visible, number}} of {{total, number}} games', {
                  visible: table.options.rowCount,
                  total: unfilteredTotal,
                  count: unfilteredTotal,
                })}
              </>
            ) : (
              <>
                {t('{{val, number}} games', {
                  count: table.options.rowCount,
                  val: table.options.rowCount,
                })}
              </>
            )}
          </p>

          <div className="flex items-center gap-2">
            <RandomGameButton
              variant="toolbar"
              apiRouteName={randomGameApiRouteName}
              columnFilters={currentFilters}
            />

            <DataTableViewOptions table={table} />
          </div>
        </div>
      </div>
    </div>
  );
}
