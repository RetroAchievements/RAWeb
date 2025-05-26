import { type Column, type Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { DataTableFacetedFilter } from '../../../DataTableFacetedFilter';

interface GameTypeFilterProps<TData> {
  table: Table<TData>;
}

export function GameTypeFilter<TData>({ table }: GameTypeFilterProps<TData>) {
  const { t } = useTranslation();

  const virtualColumn = {
    id: 'game-type',
    getFacetedUniqueValues: () => new Map(),

    getFilterValue: () =>
      table.getState().columnFilters.find((f) => f.id === 'game-type')?.value ?? [],

    setFilterValue: (value) => {
      table.setColumnFilters((prev) => [
        ...prev.filter((f) => f.id !== 'game-type'),
        { id: 'game-type', value },
      ]);
    },
  } as Column<TData, string>;

  return (
    <DataTableFacetedFilter
      t_title={t('Game type')}
      options={[
        {
          options: [
            { t_label: t('Retail'), value: 'retail' },
            { t_label: t('Hack'), value: 'hack' },
            { t_label: t('Homebrew'), value: 'homebrew' },
            { t_label: t('Prototype'), value: 'prototype' },
            { t_label: t('Unlicensed'), value: 'unlicensed' },
            { t_label: t('Demo'), value: 'demo' },
          ],
        },
      ]}
      isSearchable={false}
      column={virtualColumn}
    />
  );
}
