import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

import { DataTableFacetedFilter } from '../../DataTableFacetedFilter';

interface DataTableSystemFilterProps<TData> {
  filterableSystemOptions: App.Platform.Data.System[];
  table: Table<TData>;

  defaultOptionLabel?: TranslatedString;
  includeDefaultOption?: boolean;
  isSingleSelect?: boolean;
  variant?: 'base' | 'drawer';
}

export function DataTableSystemFilter<TData>({
  table,
  variant,
  defaultOptionLabel,
  filterableSystemOptions = [],
  includeDefaultOption = false,
  isSingleSelect = false,
}: DataTableSystemFilterProps<TData>) {
  const { t } = useTranslation();

  const systemOptions = filterableSystemOptions
    .sort((a, b) => a.name.localeCompare(b.name))
    .map((system) => ({
      t_label: system.name as TranslatedString,
      selectedLabel: system.nameShort,
      value: String(system.id),
    }));

  const options =
    includeDefaultOption && defaultOptionLabel
      ? [
          {
            t_label: defaultOptionLabel,
            value: 'supported',
          },
          ...systemOptions,
        ]
      : systemOptions;

  return (
    <DataTableFacetedFilter
      className="w-full sm:w-auto"
      column={table.getColumn('system')}
      t_title={t('System')}
      variant={variant}
      isSingleSelect={isSingleSelect}
      options={options}
    />
  );
}
