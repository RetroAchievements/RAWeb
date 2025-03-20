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

interface MobileGameTypeFilterSelect<TData> {
  table: Table<TData>;
}

export function MobileGameTypeFilterSelect<TData>({
  table,
}: MobileGameTypeFilterSelect<TData>): ReactNode {
  const { t } = useTranslation();

  const virtualColumn = {
    id: 'game-type',

    getFilterValue: () =>
      table.getState().columnFilters.find((f) => f.id === 'game-type')?.value ?? [],

    setFilterValue: (value) => {
      table.setColumnFilters((prev) => [
        ...prev.filter((f) => f.id !== 'game-type'),
        { id: 'game-type', value },
      ]);
    },
  } as Column<TData, string>;

  const selectedValues = virtualColumn.getFilterValue() as string[];

  const handleValueChange = (value: string) => {
    virtualColumn.setFilterValue([value]);
  };

  return (
    <div className="flex flex-col gap-2">
      <BaseLabel htmlFor="drawer-game-type" className="text-neutral-100 light:text-neutral-950">
        {t('Game type')}
      </BaseLabel>

      <BaseSelect value={selectedValues[0]} onValueChange={handleValueChange}>
        <BaseSelectTrigger id="drawer-game-type" className="w-full">
          <BaseSelectValue placeholder={t('All Games')} />
        </BaseSelectTrigger>

        <BaseSelectContent>
          <BaseSelectItem value="retail" data-testid="retail-option">
            {t('Retail')}
          </BaseSelectItem>

          <BaseSelectItem value="hack" data-testid="hack-option">
            {t('Hack')}
          </BaseSelectItem>

          <BaseSelectItem value="homebrew" data-testid="homebrew-option">
            {t('Homebrew')}
          </BaseSelectItem>

          <BaseSelectItem value="prototype" data-testid="prototype-option">
            {t('Prototype')}
          </BaseSelectItem>

          <BaseSelectItem value="unlicensed" data-testid="unlicensed-option">
            {t('Unlicensed')}
          </BaseSelectItem>

          <BaseSelectItem value="demo" data-testid="demo-option">
            {t('Demo')}
          </BaseSelectItem>
        </BaseSelectContent>
      </BaseSelect>
    </div>
  );
}
