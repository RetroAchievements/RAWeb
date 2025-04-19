import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseCardContent } from '@/common/components/+vendor/BaseCard';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';

import type { EmulatorSortOrder } from '../../models';
import { sortByAtom } from '../../state/downloads.atoms';

export const SortEmulators: FC = () => {
  const { t } = useTranslation();

  const [sortBy, setSortBy] = useAtom(sortByAtom);

  return (
    <div>
      <BaseCardContent className="flex flex-col gap-1.5">
        <p className="font-semibold">{t('Sort By')}</p>

        <BaseSelect
          onValueChange={(value: EmulatorSortOrder) => setSortBy(value)}
          defaultValue={sortBy}
        >
          <BaseSelectTrigger id="sort-order-select">
            <BaseSelectValue />
          </BaseSelectTrigger>

          <BaseSelectContent>
            <BaseSelectItem value="popularity">{t('Popularity')}</BaseSelectItem>
            <BaseSelectItem value="alphabetical">{t('Name (A-Z)')}</BaseSelectItem>
          </BaseSelectContent>
        </BaseSelect>
      </BaseCardContent>
    </div>
  );
};
