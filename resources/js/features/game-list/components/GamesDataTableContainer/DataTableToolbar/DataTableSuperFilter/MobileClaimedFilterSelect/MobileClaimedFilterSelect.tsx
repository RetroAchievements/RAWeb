import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectGroup,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';

interface MobileClaimedFilterSelectProps<TData> {
  table: Table<TData>;
}

export function MobileClaimedFilterSelect<TData>({ table }: MobileClaimedFilterSelectProps<TData>) {
  const { t } = useTranslation();

  const column = table.getColumn('hasActiveOrInReviewClaims');
  const filterValue = column?.getFilterValue() as string[] | undefined;
  const currentValue = filterValue?.[0] ?? 'any';

  const handleValueChange = (newValue: string) => {
    column?.setFilterValue([newValue]);
  };

  return (
    <div className="flex flex-col gap-2">
      <BaseLabel htmlFor="claimed-filter" className="text-neutral-100 light:text-neutral-950">
        {t('Claimed')}
      </BaseLabel>

      <BaseSelect value={currentValue} onValueChange={handleValueChange}>
        <BaseSelectTrigger id="claimed-filter" className="w-full">
          <BaseSelectValue placeholder={t('Claimed')} />
        </BaseSelectTrigger>

        <BaseSelectContent>
          <BaseSelectGroup>
            <BaseSelectItem value="any">{t('anyGame')}</BaseSelectItem>
            <BaseSelectItem value="claimed">{t('Claimed')}</BaseSelectItem>
            <BaseSelectItem value="unclaimed">{t('Unclaimed')}</BaseSelectItem>
          </BaseSelectGroup>
        </BaseSelectContent>
      </BaseSelect>
    </div>
  );
}
