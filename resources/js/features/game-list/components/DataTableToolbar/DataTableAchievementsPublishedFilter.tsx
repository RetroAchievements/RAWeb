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
      title="Has achievements"
      options={[
        { label: t('Yes'), value: 'has' },
        { label: t('No'), value: 'none' },
        { label: t('Both'), value: 'either' },
      ]}
      isSearchable={false}
      isSingleSelect={true}
      variant={variant}
    />
  );
}
