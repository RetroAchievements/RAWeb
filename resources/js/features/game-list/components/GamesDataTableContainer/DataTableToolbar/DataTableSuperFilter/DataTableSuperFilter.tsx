import type { ColumnDef, SortDirection, Table } from '@tanstack/react-table';
import { Fragment } from 'react/jsx-runtime';
import { useTranslation } from 'react-i18next';
import { HiFilter } from 'react-icons/hi';
import type { RouteName } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDrawer,
  BaseDrawerClose,
  BaseDrawerContent,
  BaseDrawerDescription,
  BaseDrawerFooter,
  BaseDrawerHeader,
  BaseDrawerTitle,
  BaseDrawerTrigger,
} from '@/common/components/+vendor/BaseDrawer';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectGroup,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';
import { usePageProps } from '@/common/hooks/usePageProps';
import { doesColumnExist } from '@/features/game-list/utils/doesColumnExist';

import { useSortConfigs } from '../../../../hooks/useSortConfigs';
import type { SortConfigKind } from '../../../../models';
import { DataTableSystemFilter } from '../DataTableSystemFilter';
import { RandomGameButton } from '../RandomGameButton';
import { MobileClaimedFilterSelect } from './MobileClaimedFilterSelect';
import { MobileGameTypeFilterSelect } from './MobileGameTypeFilterSelect';
import { MobileHasAchievementsFilterSelect } from './MobileHasAchievementsFilterSelect';
import { MobileProgressFilterSelect } from './MobileProgressFilterSelect';
import { MobileRequestsStatusFilterSelect } from './MobileRequestsStatusFilterSelect';
import { MobileSetTypeFilterSelect } from './MobileSetTypeFilterSelect';
import { useCurrentSuperFilterLabel } from './useCurrentSuperFilterLabel';

interface DataTableSuperFilterProps<TData> {
  table: Table<TData>;

  hasResults?: boolean;
  isTableQueryLoading?: boolean;
  randomGameApiRouteName?: RouteName;
  randomGameApiRouteParams?: Record<string, unknown>;
  tableApiRouteName?: RouteName;
}

export function DataTableSuperFilter<TData>({
  table,
  randomGameApiRouteParams,
  hasResults = false,
  isTableQueryLoading = false,
  randomGameApiRouteName = 'api.game.random',
  tableApiRouteName = 'api.game.index',
}: DataTableSuperFilterProps<TData>) {
  const { auth, filterableSystemOptions } = usePageProps<{
    filterableSystemOptions: App.Platform.Data.System[];
  }>();

  const { t } = useTranslation();

  const currentSuperFilterLabel = useCurrentSuperFilterLabel(table, tableApiRouteName);

  const { buildSortOptionLabel } = useBuildSortLabel();
  const { sortConfigs } = useSortConfigs();

  const allColumns = table.getAllColumns();

  const sortableColumns = allColumns.filter((c) => c.getCanSort());
  const sortingState = table.getState().sorting;

  const currentSort = sortingState.length
    ? sortingState.map((sort) => (sort.desc ? `-${sort.id}` : sort.id)).join()
    : 'title';

  const currentFilters = table.getState().columnFilters;

  const handleSortOrderValueChange = (newValue: string) => {
    const direction = newValue.startsWith('-') ? 'desc' : 'asc';
    const columnId = newValue.replace('-', '');

    table.getColumn(columnId)?.toggleSorting(direction === 'desc');

    // Track the most common sorts.
    if (window.plausible) {
      window.plausible('Game List Sort', { props: { order: newValue } });
    }
  };

  return (
    <BaseDrawer shouldScaleBackground={false}>
      <BaseDrawerTrigger asChild>
        <button className="flex items-center gap-1 tracking-tight text-neutral-200 light:text-neutral-950">
          {currentSuperFilterLabel}
          <HiFilter className="h-4 w-4" />
        </button>
      </BaseDrawerTrigger>

      <BaseDrawerContent>
        <div className="mx-auto w-full max-w-sm">
          <BaseDrawerHeader>
            <BaseDrawerTitle>{t('Customize View')}</BaseDrawerTitle>
            <BaseDrawerDescription className="sr-only">{t('Customize View')}</BaseDrawerDescription>
          </BaseDrawerHeader>

          <div className="flex flex-col gap-4 p-4">
            {!tableApiRouteName.includes('api.set-request') ? (
              <div className="grid grid-cols-2 gap-2">
                <MobileGameTypeFilterSelect table={table} />
                <MobileSetTypeFilterSelect table={table} />
              </div>
            ) : null}

            {doesColumnExist(allColumns, 'system') && filterableSystemOptions?.length > 1 ? (
              <DataTableSystemFilter
                table={table}
                defaultOptionLabel={
                  tableApiRouteName === 'api.set-request.user'
                    ? t('All systems')
                    : t('Only supported systems')
                }
                defaultOptionValue={
                  tableApiRouteName === 'api.set-request.user' ? 'all' : 'supported'
                }
                filterableSystemOptions={filterableSystemOptions}
                includeDefaultOption={tableApiRouteName.includes('api.set-request')}
                isSingleSelect={tableApiRouteName.includes('api.set-request')}
                variant="drawer"
              />
            ) : null}

            {tableApiRouteName.includes('api.set-request') ? (
              <MobileClaimedFilterSelect table={table} />
            ) : null}

            {tableApiRouteName === 'api.set-request.user' ? (
              <MobileRequestsStatusFilterSelect table={table} />
            ) : null}

            {!tableApiRouteName.includes('api.set-request') ? (
              <MobileHasAchievementsFilterSelect table={table} />
            ) : null}

            {auth?.user && !tableApiRouteName.includes('api.set-request') ? (
              <MobileProgressFilterSelect table={table} />
            ) : null}

            {/* If the sort order field isn't on the bottom of the drawer, the select content gets cut off the screen. */}
            <div className="flex flex-col gap-2">
              <BaseLabel htmlFor="supersort" className="text-neutral-100 light:text-neutral-950">
                {t('Sort order')}
              </BaseLabel>

              <BaseSelect value={currentSort} onValueChange={handleSortOrderValueChange}>
                <BaseSelectTrigger id="supersort" className="w-full">
                  <BaseSelectValue placeholder={t('Sort order')} />
                </BaseSelectTrigger>

                <BaseSelectContent>
                  <BaseSelectGroup>
                    {sortableColumns.map((column) => {
                      const sortType = (column.columnDef.meta?.sortType ??
                        'default') as SortConfigKind;
                      const sortConfig = sortConfigs[sortType];

                      return (Object.keys(sortConfig) as SortDirection[]).map((direction) => (
                        <Fragment key={`${column.id}-${direction}`}>
                          <BaseSelectItem
                            value={direction === 'asc' ? column.id : `-${column.id}`}
                            Icon={column.columnDef.meta?.Icon}
                          >
                            {buildSortOptionLabel(column.columnDef, direction)}
                          </BaseSelectItem>
                        </Fragment>
                      ));
                    })}
                  </BaseSelectGroup>
                </BaseSelectContent>
              </BaseSelect>
            </div>
          </div>

          <BaseDrawerFooter>
            <div className="grid grid-cols-2 gap-3">
              <RandomGameButton
                variant="mobile-drawer"
                apiRouteName={randomGameApiRouteName}
                apiRouteParams={randomGameApiRouteParams}
                columnFilters={currentFilters}
                disabled={!hasResults || isTableQueryLoading}
              />

              <BaseDrawerClose asChild>
                <BaseButton variant="secondary">{t('Close')}</BaseButton>
              </BaseDrawerClose>
            </div>
          </BaseDrawerFooter>
        </div>
      </BaseDrawerContent>
    </BaseDrawer>
  );
}

function useBuildSortLabel() {
  const { sortConfigs } = useSortConfigs();

  const buildSortOptionLabel = <TData,>(
    columnDef: ColumnDef<TData, unknown>,
    direction: 'asc' | 'desc',
  ) => {
    const sortType = (columnDef.meta?.sortType ?? 'default') as SortConfigKind;
    const sortConfig = sortConfigs[sortType];

    const sortLabel = sortConfig[direction].t_label;

    return `${columnDef.meta?.t_label}, ${sortLabel}`;
  };

  return { buildSortOptionLabel };
}
