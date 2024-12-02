import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

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
          t_label: system.name as TranslatedString,
          selectedLabel: system.nameShort,
          value: String(system.id),
        }))}
    />
  );
}
