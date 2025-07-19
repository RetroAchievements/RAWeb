import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { DataTableFacetedFilter } from '../../DataTableFacetedFilter';

interface DataTableClaimedFilterProps<TData> {
  table: Table<TData>;

  variant?: 'base' | 'drawer';
}

export function DataTableClaimedFilter<TData>({
  table,
  variant = 'base',
}: DataTableClaimedFilterProps<TData>) {
  const { t } = useTranslation();

  return (
    <DataTableFacetedFilter
      className="w-full sm:w-auto"
      column={table.getColumn('hasActiveOrInReviewClaims')}
      t_title={t('Claimed')}
      options={[
        { t_label: t('anyGame'), value: 'any' },
        { t_label: t('Claimed'), value: 'claimed' },
        { t_label: t('Unclaimed'), value: 'unclaimed' },
      ]}
      isSearchable={false}
      isSingleSelect={true}
      variant={variant}
    />
  );
}
