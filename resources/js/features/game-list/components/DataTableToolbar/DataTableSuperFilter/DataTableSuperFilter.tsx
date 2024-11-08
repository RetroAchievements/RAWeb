import type { ColumnDef, SortDirection, Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Fragment } from 'react/jsx-runtime';
import { HiFilter } from 'react-icons/hi';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDrawer,
  BaseDrawerClose,
  BaseDrawerContent,
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

import { useSortConfigs } from '../../../hooks/useSortConfigs';
import type { SortConfigKind } from '../../../models';
import { DataTableAchievementsPublishedFilter } from '../DataTableAchievementsPublishedFilter';
import { DataTableSystemFilter } from '../DataTableSystemFilter';
import { useCurrentSuperFilterLabel } from './useCurrentSuperFilterLabel';

interface DataTableSuperFilterProps<TData> {
  table: Table<TData>;
}

export function DataTableSuperFilter<TData>({ table }: DataTableSuperFilterProps<TData>) {
  const { filterableSystemOptions } = usePageProps<{
    filterableSystemOptions: App.Platform.Data.System[];
  }>();

  const { t } = useLaravelReactI18n();

  const currentSuperFilterLabel = useCurrentSuperFilterLabel(table);

  const { buildSortOptionLabel } = useBuildSortLabel();

  const { sortConfigs } = useSortConfigs();

  const sortableColumns = table.getAllColumns().filter((c) => c.getCanSort());

  const currentSort =
    table
      .getState()
      .sorting.map((sort) => (sort.desc ? `-${sort.id}` : sort.id))
      .join() ?? 'title';

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
    <BaseDrawer shouldScaleBackground={false} modal={false}>
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
          </BaseDrawerHeader>

          <div className="flex flex-col gap-4 p-4">
            <DataTableAchievementsPublishedFilter table={table} variant="drawer" />

            <DataTableSystemFilter
              filterableSystemOptions={filterableSystemOptions}
              table={table}
              variant="drawer"
            />

            {/* If the sort order field isn't on the bottom of the drawer, the select content gets cut off the screen. */}
            <div className="flex flex-col gap-2">
              <BaseLabel htmlFor="supersort" className="text-neutral-100 light:text-neutral-950">
                {t('Sort order')}
              </BaseLabel>

              <BaseSelect value={currentSort} onValueChange={handleSortOrderValueChange}>
                <BaseSelectTrigger id="supersort" className="w-full">
                  <BaseSelectValue placeholder={t('Sort order')}></BaseSelectValue>
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
            <BaseDrawerClose asChild>
              <BaseButton variant="secondary">{t('Close')}</BaseButton>
            </BaseDrawerClose>
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
