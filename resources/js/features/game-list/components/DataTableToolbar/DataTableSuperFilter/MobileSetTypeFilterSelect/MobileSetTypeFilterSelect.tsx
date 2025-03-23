import type { Column, Table } from '@tanstack/react-table';
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

interface MobileSetTypeFilterSelect<TData> {
  table: Table<TData>;
}

export function MobileSetTypeFilterSelect<TData>({
  table,
}: MobileSetTypeFilterSelect<TData>): ReactNode {
  const { t } = useTranslation();

  const virtualColumn = {
    id: 'subsets',

    getFilterValue: () =>
      table.getState().columnFilters.find((f) => f.id === 'subsets')?.value ?? [],

    setFilterValue: (value) => {
      table.setColumnFilters((prev) => [
        ...prev.filter((f) => f.id !== 'subsets'),
        { id: 'subsets', value },
      ]);
    },
  } as Column<TData, string>;

  const selectedValues = virtualColumn.getFilterValue() as string[];

  const handleValueChange = (value: App.Platform.Enums.GameListSetTypeFilterValue | 'null') => {
    if (value === 'null') {
      virtualColumn.setFilterValue(undefined);

      return;
    }

    virtualColumn.setFilterValue([value]);
  };

  return (
    <div className="flex flex-col gap-2">
      <BaseLabel
        htmlFor="drawer-achievements-published"
        className="text-neutral-100 light:text-neutral-950"
      >
        {t('Set type')}
      </BaseLabel>

      <BaseSelect value={selectedValues[0]} onValueChange={handleValueChange}>
        <BaseSelectTrigger id="drawer-set-type" className="w-full">
          <BaseSelectValue placeholder={t('All Sets')} />
        </BaseSelectTrigger>

        <BaseSelectContent>
          <BaseSelectItem value="null" data-testid="all-sets-option">
            {t('All Sets')}
          </BaseSelectItem>

          <BaseSelectItem value="only-games" data-testid="only-games-option">
            {t('Main Sets Only')}
          </BaseSelectItem>

          <BaseSelectItem value="only-subsets" data-testid="only-subsets-option">
            {t('Subsets Only')}
          </BaseSelectItem>
        </BaseSelectContent>
      </BaseSelect>
    </div>
  );
}
