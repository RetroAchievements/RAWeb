import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';

interface MobileRequestsStatusFilterSelectProps<TData> {
  table: Table<TData>;
}

export function MobileRequestsStatusFilterSelect<TData>({
  table,
}: MobileRequestsStatusFilterSelectProps<TData>) {
  const { t } = useTranslation();

  const column = table.getColumn('achievementsPublished');
  const currentFilter = (column?.getFilterValue() as string[] | undefined)?.[0] ?? 'none';

  const handleValueChange = (newValue: string) => {
    column?.setFilterValue([newValue]);
  };

  return (
    <div className="flex flex-col gap-2">
      <BaseLabel
        htmlFor="mobile-requests-status"
        className="text-xs text-neutral-100 light:text-neutral-950"
      >
        {t('Requests')}
      </BaseLabel>

      <BaseSelect value={currentFilter} onValueChange={handleValueChange}>
        <BaseSelectTrigger id="mobile-requests-status" className="h-10">
          <BaseSelectValue />
        </BaseSelectTrigger>

        <BaseSelectContent>
          <BaseSelectItem value="none">{t('filterRequests_active')}</BaseSelectItem>
          <BaseSelectItem value="either">{t('filterRequests_all')}</BaseSelectItem>
        </BaseSelectContent>
      </BaseSelect>
    </div>
  );
}
