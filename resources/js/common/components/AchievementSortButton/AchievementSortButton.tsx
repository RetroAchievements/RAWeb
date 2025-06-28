import { type FC, Fragment } from 'react';
import { useTranslation } from 'react-i18next';
import { LuArrowDown, LuArrowUp } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuCheckboxItem,
  BaseDropdownMenuContent,
  BaseDropdownMenuLabel,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import type { AchievementSortOrder } from '@/common/models';
import type { TranslatedString } from '@/types/i18next';

interface AchievementSortButtonProps {
  availableSortOrders: AchievementSortOrder[];
  onChange: (newValue: AchievementSortOrder) => void;
  value: AchievementSortOrder;
}

export const AchievementSortButton: FC<AchievementSortButtonProps> = ({
  availableSortOrders,
  onChange,
  value,
}) => {
  const { t } = useTranslation();

  const sortOptionsLabelMap: Record<AchievementSortOrder, TranslatedString> = {
    active: t('Status'),

    '-normal': t('Display order (last)'),
    normal: t('Display order (first)'),

    '-displayOrder': t('Display order (last)'),
    displayOrder: t('Display order (first)'),

    '-points': t('Points (least)'),
    points: t('Points (most)'),

    '-title': t('Title (Z - A)'),
    title: t('Title (A - Z)'),

    '-type': t('Type (desc)'),
    type: t('Type (asc)'),

    '-wonBy': t('Won by (least)'),
    wonBy: t('Won by (most)'),
  };

  return (
    <BaseDropdownMenu>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton
          size="sm"
          className="gap-1 transition-none lg:active:translate-y-0 lg:active:scale-100"
        >
          {value.startsWith('-') ? (
            <LuArrowDown data-testid="sort-descending-icon" className="size-4" />
          ) : (
            <LuArrowUp data-testid="sort-ascending-icon" className="size-4" />
          )}
          {sortOptionsLabelMap[value]}
        </BaseButton>
      </BaseDropdownMenuTrigger>

      <BaseDropdownMenuContent align="start">
        <BaseDropdownMenuLabel>{t('Sort order')}</BaseDropdownMenuLabel>
        <BaseDropdownMenuSeparator />

        {availableSortOrders.map((sortOrder) => (
          <Fragment key={`option-item-${sortOrder}`}>
            <BaseDropdownMenuCheckboxItem
              checked={value === sortOrder}
              onCheckedChange={() => onChange(sortOrder)}
            >
              {sortOptionsLabelMap[sortOrder]}
            </BaseDropdownMenuCheckboxItem>
          </Fragment>
        ))}
      </BaseDropdownMenuContent>
    </BaseDropdownMenu>
  );
};
