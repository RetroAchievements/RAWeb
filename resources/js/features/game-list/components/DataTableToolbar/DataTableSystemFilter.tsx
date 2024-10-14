import type { Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { DataTableFacetedFilter } from '../DataTableFacetedFilter';

interface DataTableSystemFilterProps<TData> {
  filterableSystemOptions: App.Platform.Data.System[];
  table: Table<TData>;

  variant?: 'base' | 'drawer';
}

export function DataTableSystemFilter<TData>({
  filterableSystemOptions,
  table,
  variant,
}: DataTableSystemFilterProps<TData>) {
  const { t } = useLaravelReactI18n();

  return (
    <DataTableFacetedFilter
      className="w-full sm:w-auto"
      column={table.getColumn('system')}
      title={t('System')}
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
