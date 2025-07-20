import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { DataTableFacetedFilter } from '../../DataTableFacetedFilter';

interface DataTableRequestsStatusFilterProps<TData> {
  table: Table<TData>;

  variant?: 'base' | 'drawer';
}

export function DataTableRequestsStatusFilter<TData>({
  table,
  variant = 'base',
}: DataTableRequestsStatusFilterProps<TData>) {
  const { t } = useTranslation();

  return (
    <DataTableFacetedFilter
      className="w-full sm:w-auto"
      column={table.getColumn('achievementsPublished')}
      t_title={t('Requests')}
      options={[
        { t_label: t('filterRequests_active'), value: 'none' },
        { t_label: t('filterRequests_all'), value: 'either' },
      ]}
      isSearchable={false}
      isSingleSelect={true}
      variant={variant}
    />
  );
}
