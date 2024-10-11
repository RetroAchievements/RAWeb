import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { RxCross2 } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { getAreNonDefaultFiltersSet } from '../../utils/getAreNonDefaultFiltersSet';
import { DataTableFacetedFilter } from '../DataTableFacetedFilter';
import { DataTableSearchInput } from '../DataTableSearchInput';
import { DataTableViewOptions } from '../DataTableViewOptions';

interface WantToPlayGamesDataTableToolbarProps<TData> {
  table: Table<TData>;
  unfilteredTotal: number | null;

  defaultColumnFilters?: ColumnFiltersState;
}

export function WantToPlayGamesDataTableToolbar<TData>({
  table,
  unfilteredTotal,
  defaultColumnFilters = [],
}: WantToPlayGamesDataTableToolbarProps<TData>) {
  const { filterableSystemOptions } = usePageProps<App.Community.Data.UserGameListPageProps>();

  const { t, tChoice } = useLaravelReactI18n();

  const currentFilters = table.getState().columnFilters;
  const isFiltered = getAreNonDefaultFiltersSet(currentFilters, defaultColumnFilters);

  const resetFiltersToDefault = () => {
    if (defaultColumnFilters) {
      table.setColumnFilters(defaultColumnFilters);
    } else {
      table.resetColumnFilters();
    }
  };

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
          <DataTableFacetedFilter
            className="w-full sm:w-auto"
            column={table.getColumn('achievementsPublished')}
            title={t('Has achievements')}
            options={[
              { label: t('Yes'), value: 'has' },
              { label: t('No'), value: 'none' },
              { label: t('Either'), value: 'either' },
            ]}
            isSearchable={false}
            isSingleSelect={true}
          />
        ) : null}

        {isFiltered ? (
          <BaseButton
            variant="ghost"
            size="sm"
            onClick={resetFiltersToDefault}
            className="border-dashed px-2 text-link lg:px-3"
          >
            {t('Reset')} <RxCross2 className="ml-2 h-4 w-4" />
          </BaseButton>
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
