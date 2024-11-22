import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { DataTableFacetedFilter } from '../DataTableFacetedFilter';

interface DataTableSystemFilterProps<TData> {
  filterableSystemOptions: App.Platform.Data.System[];
  table: Table<TData>;

  variant?: 'base' | 'drawer';
}

export function DataTableSystemFilter<TData>({
  table,
  variant,
  filterableSystemOptions = [],
}: DataTableSystemFilterProps<TData>) {
  const { t } = useTranslation();

  return (
    <DataTableFacetedFilter
      className="w-full sm:w-auto"
      column={table.getColumn('system')}
      t_title={t('System')}
      variant={variant}
      options={filterableSystemOptions
        .sort((a, b) => a.name.localeCompare(b.name))
        .map((system) => ({
          label: system.name,
          selectedLabel: system.nameShort,
          value: String(system.id),
        }))}
    />
  );
}
