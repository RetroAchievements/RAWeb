import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';

import { BaseChip } from '../+vendor/BaseChip';

interface SubsetTagProps {
  className?: string;
  type?: App.Platform.Enums.AchievementSetType;
}

export const SubsetTag: FC<SubsetTagProps> = ({ className, type }) => {
  const { t } = useTranslation();

  let label = t('Subset');
  if (type === 'bonus') {
    label = t('Bonus Subset');
  } else if (type === 'specialty') {
    label = t('Specialty Subset');
  } else if (type === 'exclusive') {
    label = t('Exclusive Subset');
  }

  return (
    <BaseChip
      className={cn(
        'rounded bg-menu-link px-2 py-0 text-sm text-neutral-950 light:bg-neutral-600 light:text-white',
        className,
      )}
    >
      {label}
    </BaseChip>
  );
};
