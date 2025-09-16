import { type FC, Fragment } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons/lib';
import {
  LuArrowDown,
  LuArrowDown01,
  LuArrowDown10,
  LuArrowDownAZ,
  LuArrowDownZA,
  LuArrowUp,
  LuClock,
  LuLockOpen,
  LuTag,
  LuUsers,
} from 'react-icons/lu';
import { PiMedalFill } from 'react-icons/pi';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuCheckboxItem,
  BaseDropdownMenuContent,
  BaseDropdownMenuLabel,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import type { PlayableListSortOrder } from '@/common/models';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

interface PlayableListSortButtonProps {
  availableSortOrders: PlayableListSortOrder[];
  onChange: (newValue: PlayableListSortOrder) => void;
  value: PlayableListSortOrder;

  buttonClassName?: string;
  disabled?: boolean;
}

export const PlayableListSortButton: FC<PlayableListSortButtonProps> = ({
  availableSortOrders,
  buttonClassName,
  disabled,
  onChange,
  value,
}) => {
  const { t } = useTranslation();

  const sortOptionsLabelMap: Record<
    PlayableListSortOrder,
    { label: TranslatedString; icon: IconType }
  > = {
    active: { label: t('Status'), icon: LuClock },

    normal: { label: t('Unlocked first'), icon: LuLockOpen },

    '-displayOrder': { label: t('Display order (last)'), icon: LuArrowDown10 },
    displayOrder: { label: t('Display order (first)'), icon: LuArrowDown01 },

    '-points': { label: t('Points (least)'), icon: PiMedalFill },
    points: { label: t('Points (most)'), icon: PiMedalFill },

    '-title': { label: t('Title (Z - A)'), icon: LuArrowDownZA },
    title: { label: t('Title (A - Z)'), icon: LuArrowDownAZ },

    '-type': { label: t('Type (desc)'), icon: LuTag },
    type: { label: t('Type (asc)'), icon: LuTag },

    '-wonBy': { label: t('Won by (least)'), icon: LuUsers },
    wonBy: { label: t('Won by (most)'), icon: LuUsers },
  };

  return (
    <BaseDropdownMenu>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton
          size="sm"
          className={cn(
            'gap-1 transition-none lg:active:translate-y-0 lg:active:scale-100',
            buttonClassName,
          )}
          disabled={disabled}
        >
          {value.startsWith('-') ? (
            <LuArrowDown data-testid="sort-descending-icon" className="size-4" />
          ) : (
            <LuArrowUp data-testid="sort-ascending-icon" className="size-4" />
          )}
          {sortOptionsLabelMap[value].label}
        </BaseButton>
      </BaseDropdownMenuTrigger>

      <BaseDropdownMenuContent align="start">
        <BaseDropdownMenuLabel>{t('Sort order')}</BaseDropdownMenuLabel>
        <BaseDropdownMenuSeparator />

        {availableSortOrders.map((sortOrder) => {
          const SortIcon = sortOptionsLabelMap[sortOrder].icon;

          return (
            <Fragment key={`option-item-${sortOrder}`}>
              <BaseDropdownMenuCheckboxItem
                checked={value === sortOrder}
                onCheckedChange={() => onChange(sortOrder)}
                className="gap-2"
              >
                <SortIcon className="size-5" />
                {sortOptionsLabelMap[sortOrder].label}
              </BaseDropdownMenuCheckboxItem>
            </Fragment>
          );
        })}
      </BaseDropdownMenuContent>
    </BaseDropdownMenu>
  );
};
