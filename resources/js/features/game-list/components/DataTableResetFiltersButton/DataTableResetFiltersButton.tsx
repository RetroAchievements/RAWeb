import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';
import { RxCross2 } from 'react-icons/rx';
import type { RouteName } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';

import { useDataTablePrefetchResetFilters } from '../../hooks/useDataTablePrefetchResetFilters';

interface DataTableResetFiltersButtonProps<TData> {
  table: Table<TData>;

  defaultColumnFilters?: ColumnFiltersState;
  /** The controller route name where client-side calls for this datatable are made. */
  tableApiRouteName?: RouteName;
}

export function DataTableResetFiltersButton<TData>({
  table,
  defaultColumnFilters = [],
  tableApiRouteName = 'api.game.index',
}: DataTableResetFiltersButtonProps<TData>) {
  const { t } = useTranslation();

  const { prefetchResetFilters } = useDataTablePrefetchResetFilters(
    table,
    defaultColumnFilters,
    tableApiRouteName,
  );

  const resetFiltersToDefault = () => {
    table.setColumnFilters(defaultColumnFilters);
  };

  return (
    <BaseButton
      variant="ghost"
      size="sm"
      onClick={resetFiltersToDefault}
      onMouseEnter={() => prefetchResetFilters()}
      className="px-2 text-link lg:px-3"
      data-testid="reset-all-filters"
    >
      {t('Reset')} <RxCross2 className="ml-2 h-4 w-4" />
    </BaseButton>
  );
}
