import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

import { DataTableFacetedFilter, type FacetedFilterOption } from '../../DataTableFacetedFilter';

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

  const defaultOptions: FacetedFilterOption[] = [];

  if (includeDefaultOption && defaultOptionLabel) {
    // Add the primary default option (eg: "All supported systems").
    defaultOptions.push({
      t_label: defaultOptionLabel,
      value: 'supported',
    });

    // In single-select mode, add an "All systems" option.
    // The default option is probably a filtered set of systems.
    if (isSingleSelect) {
      defaultOptions.push({
        t_label: t('All systems'),
        value: 'all',
      });
    }
  }

  // Combine the default options with sorted system options.
  const options = defaultOptions.length > 0 ? [...defaultOptions, ...systemOptions] : systemOptions;

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
