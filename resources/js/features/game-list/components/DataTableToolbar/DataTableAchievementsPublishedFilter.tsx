import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { DataTableFacetedFilter } from '../DataTableFacetedFilter';

interface DataTableAchievementsPublishedFilterProps<TData> {
  table: Table<TData>;

  variant?: 'base' | 'drawer';
}

export function DataTableAchievementsPublishedFilter<TData>({
  table,
  variant = 'base',
}: DataTableAchievementsPublishedFilterProps<TData>) {
  const { t } = useTranslation();

  return (
    <DataTableFacetedFilter
      className="w-full sm:w-auto"
      column={table.getColumn('achievementsPublished')}
      t_title={t('Has achievements')}
      options={[
        { t_label: t('Yes'), value: 'has' },
        { t_label: t('No'), value: 'none' },
        { t_label: t('Both'), value: 'either' },
      ]}
      isSearchable={false}
      isSingleSelect={true}
      variant={variant}
    />
  );
}
