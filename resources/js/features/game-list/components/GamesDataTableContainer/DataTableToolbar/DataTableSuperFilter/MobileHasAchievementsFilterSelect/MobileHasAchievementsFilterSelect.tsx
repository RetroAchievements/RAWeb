import type { Table } from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';

interface MobileHasAchievementsFilterSelect<TData> {
  table: Table<TData>;
}

export function MobileHasAchievementsFilterSelect<TData>({
  table,
}: MobileHasAchievementsFilterSelect<TData>): ReactNode {
  const { t } = useTranslation();

  const column = table.getColumn('achievementsPublished');
  const selectedValues = column?.getFilterValue() as string[];

  const handleValueChange = (value: 'has' | 'none' | 'either') => {
    column?.setFilterValue([value]);
  };

  return (
    <div className="flex flex-col gap-2">
      <BaseLabel
        htmlFor="drawer-achievements-published"
        className="text-neutral-100 light:text-neutral-950"
      >
        {t('Has achievements')}
      </BaseLabel>

      <BaseSelect value={selectedValues?.[0]} onValueChange={handleValueChange}>
        <BaseSelectTrigger id="drawer-achievements-published" className="w-full">
          <BaseSelectValue placeholder={t('Both')} />
        </BaseSelectTrigger>

        <BaseSelectContent>
          <BaseSelectItem value="has" data-testid="has-option">
            {t('Yes')}
          </BaseSelectItem>

          <BaseSelectItem value="none" data-testid="none-option">
            {t('No')}
          </BaseSelectItem>

          <BaseSelectItem value="either" data-testid="either-option">
            {t('Both')}
          </BaseSelectItem>
        </BaseSelectContent>
      </BaseSelect>
    </div>
  );
}
