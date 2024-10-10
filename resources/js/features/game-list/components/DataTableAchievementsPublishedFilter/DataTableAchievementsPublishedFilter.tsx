import type { Table } from '@tanstack/react-table';

import { DataTableFacetedFilter } from '../DataTableFacetedFilter';

interface DataTableAchievementsPublishedFilterProps<TData> {
  table: Table<TData>;
}

export function DataTableAchievementsPublishedFilter<TData>({
  table,
}: DataTableAchievementsPublishedFilterProps<TData>) {
  return (
    <DataTableFacetedFilter
      className="w-full sm:w-auto"
      column={table.getColumn('achievementsPublished')}
      title="Has achievements"
      options={[
        { label: 'Yes', value: 'has' },
        { label: 'No', value: 'none' },
        { label: 'Both', value: 'either' },
      ]}
      isSearchable={false}
      isSingleSelect={true}
    />
  );
}
