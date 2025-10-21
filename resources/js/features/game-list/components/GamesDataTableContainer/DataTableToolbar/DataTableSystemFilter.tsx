import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

import { DataTableFacetedFilter, type FacetedFilterOption } from '../../DataTableFacetedFilter';

interface DataTableSystemFilterProps<TData> {
  filterableSystemOptions: App.Platform.Data.System[];
  table: Table<TData>;

  defaultOptionLabel?: TranslatedString;
  defaultOptionValue?: 'supported' | 'all';
  includeDefaultOption?: boolean;
  isSingleSelect?: boolean;
  variant?: 'base' | 'drawer';
}

export function DataTableSystemFilter<TData>({
  table,
  variant,
  defaultOptionLabel,
  defaultOptionValue = 'supported',
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
    // In single-select mode, add both the primary default option and "All systems".
    // The option matching defaultOptionValue is marked as the default.
    if (isSingleSelect) {
      if (defaultOptionValue === 'all') {
        // "All systems" is the default option.
        defaultOptions.push({
          t_label: t('All systems'),
          value: 'all',
          isDefaultOption: true,
        });
        defaultOptions.push({
          t_label: t('Only supported systems'),
          value: 'supported',
        });
      } else {
        // "Only supported systems" is the default option.
        defaultOptions.push({
          t_label: defaultOptionLabel,
          value: 'supported',
          isDefaultOption: true,
        });
        defaultOptions.push({
          t_label: t('All systems'),
          value: 'all',
        });
      }
    } else {
      // Multi-select mode: just add the primary default option.
      defaultOptions.push({
        t_label: defaultOptionLabel,
        value: defaultOptionValue,
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
