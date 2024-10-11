import type { Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { DataTableFacetedFilter } from '../DataTableFacetedFilter';

interface DataTableAchievementsPublishedFilterProps<TData> {
  table: Table<TData>;
}

export function DataTableAchievementsPublishedFilter<TData>({
  table,
}: DataTableAchievementsPublishedFilterProps<TData>) {
  const { t } = useLaravelReactI18n();

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
    />
  );
}
