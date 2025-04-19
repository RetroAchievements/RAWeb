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

interface SortOption {
  value: AchievementSortOrder;
  label: TranslatedString;
  negativeLabel?: TranslatedString;
}

interface AchievementSortButtonProps {
  value: AchievementSortOrder;
  onChange: (newValue: AchievementSortOrder) => void;

  /**
   * Whether or not the "Active" sort option (for events) should be displayed.
   * Typically disabled for evergreen events and games.
   */
  includeActiveOption?: boolean;
}

export const AchievementSortButton: FC<AchievementSortButtonProps> = ({
  onChange,
  value,
  includeActiveOption = false,
}) => {
  const { t } = useTranslation();

  const sortOptions: SortOption[] = [
    {
      value: 'displayOrder',
      label: t('Display order (first)'),
      negativeLabel: t('Display order (last)'),
    },
    {
      value: 'wonBy',
      label: t('Won by (most)'),
      negativeLabel: t('Won by (least)'),
    },
  ];

  // If the active option is enabled, add it to the beginning of the sort options.
  if (includeActiveOption) {
    sortOptions.unshift({
      value: 'active',
      label: t('Status'),
    });
  }

  const getCurrentLabel = () => {
    const isNegative = value.startsWith('-');
    const baseValue = isNegative ? value.slice(1) : value;

    const option = sortOptions.find((opt) => opt.value === baseValue) as SortOption;

    return isNegative ? option.negativeLabel : option.label;
  };

  return (
    <BaseDropdownMenu>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton size="sm" className="gap-1">
          {value.startsWith('-') ? (
            <LuArrowDown data-testid="sort-descending-icon" className="size-4" />
          ) : (
            <LuArrowUp data-testid="sort-ascending-icon" className="size-4" />
          )}
          {getCurrentLabel()}
        </BaseButton>
      </BaseDropdownMenuTrigger>

      <BaseDropdownMenuContent align="start">
        <BaseDropdownMenuLabel>{t('Sort order')}</BaseDropdownMenuLabel>
        <BaseDropdownMenuSeparator />

        {sortOptions.map((option) => (
          <Fragment key={option.value}>
            <BaseDropdownMenuCheckboxItem
              checked={value === option.value}
              onCheckedChange={() => onChange(option.value as AchievementSortOrder)}
            >
              {option.label}
            </BaseDropdownMenuCheckboxItem>

            {option.negativeLabel ? (
              <BaseDropdownMenuCheckboxItem
                checked={value === `-${option.value}`}
                onCheckedChange={() => onChange(`-${option.value}` as AchievementSortOrder)}
              >
                {option.negativeLabel}
              </BaseDropdownMenuCheckboxItem>
            ) : null}
          </Fragment>
        ))}
      </BaseDropdownMenuContent>
    </BaseDropdownMenu>
  );
};
