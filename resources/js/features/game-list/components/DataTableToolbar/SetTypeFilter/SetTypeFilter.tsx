import type { Column, Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { DataTableFacetedFilter } from '../../DataTableFacetedFilter';

interface SetTypeFilterProps<TData> {
  table: Table<TData>;
}

export function SetTypeFilter<TData>({ table }: SetTypeFilterProps<TData>) {
  const { t } = useTranslation();

  const virtualColumn = {
    id: 'subsets',
    getFacetedUniqueValues: () => new Map(),

    getFilterValue: () =>
      table.getState().columnFilters.find((f) => f.id === 'subsets')?.value ?? [],

    setFilterValue: (value) => {
      table.setColumnFilters((prev) => [
        ...prev.filter((f) => f.id !== 'subsets'),
        { id: 'subsets', value },
      ]);
    },
  } as Column<TData, string>;

  return (
    <DataTableFacetedFilter
      t_title={t('Set type')}
      options={[
        {
          options: [
            { t_label: t('All Sets'), value: 'both' },
            { t_label: t('Main Sets Only'), value: 'only-games' },
            { t_label: t('Subsets Only'), value: 'only-subsets' },
          ],
        },
      ]}
      isSearchable={false}
      isSingleSelect={true}
      column={virtualColumn}
    />
  );
}
