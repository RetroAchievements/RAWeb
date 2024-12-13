import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import { useAtom } from 'jotai';
import { useTranslation } from 'react-i18next';
import type { RouteName } from 'ziggy-js';

import { BaseCheckbox } from '@/common/components/+vendor/BaseCheckbox';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import { usePageProps } from '@/common/hooks/usePageProps';

import { isCurrentlyPersistingViewAtom } from '../../state/game-list.atoms';
import { doesColumnExist } from '../../utils/doesColumnExist';
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
  tableApiRouteParams?: Record<string, unknown>;
}

export function DataTableDesktopToolbar<TData>({
  table,
  tableApiRouteParams,
  unfilteredTotal,
  defaultColumnFilters = [],
  randomGameApiRouteName = 'api.game.random',
  tableApiRouteName = 'api.game.index',
}: DataTableDesktopToolbarProps<TData>) {
  const { filterableSystemOptions } = usePageProps<{
    filterableSystemOptions: App.Platform.Data.System[];
  }>();

  const { t } = useTranslation();

  const [isCurrentlyPersistingView, setIsCurrentlyPersistingView] = useAtom(
    isCurrentlyPersistingViewAtom,
  );

  const allColumns = table.getAllColumns();

  const currentFilters = table.getState().columnFilters;
  const isFiltered = getAreNonDefaultFiltersSet(currentFilters, defaultColumnFilters);

  return (
    <div className="flex w-full flex-col justify-between gap-2">
      <div className="flex w-full flex-col items-center justify-between gap-3 rounded bg-embed py-2 pl-2 pr-3 sm:flex-row">
        <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row md:gap-3">
          {doesColumnExist(allColumns, 'system') && filterableSystemOptions?.length > 1 ? (
            <DataTableSystemFilter
              table={table}
              filterableSystemOptions={filterableSystemOptions}
            />
          ) : null}

          {doesColumnExist(allColumns, 'achievementsPublished') ? (
            <DataTableAchievementsPublishedFilter table={table} />
          ) : null}

          {doesColumnExist(allColumns, 'progress') ? (
            <DataTableProgressFilter table={table} />
          ) : null}

          {isFiltered ? (
            <DataTableResetFiltersButton
              table={table}
              defaultColumnFilters={defaultColumnFilters}
              tableApiRouteName={tableApiRouteName}
              tableApiRouteParams={tableApiRouteParams}
            />
          ) : null}
        </div>

        <BaseLabel className="flex items-center gap-2 text-menu-link">
          <BaseCheckbox
            checked={isCurrentlyPersistingView}
            onCheckedChange={(checked: boolean) => setIsCurrentlyPersistingView(checked)}
          />

          {t('Remember my view')}
        </BaseLabel>
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
              apiRouteParams={tableApiRouteParams}
              columnFilters={currentFilters}
              disabled={table.getRowCount() === 0}
            />

            <DataTableViewOptions table={table} />
          </div>
        </div>
      </div>
    </div>
  );
}
