import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuLayers } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { cn } from '@/common/utils/cn';

interface SubsetButtonChipProps {
  className?: string;
}

export const SubsetButtonChip: FC<SubsetButtonChipProps> = ({ className }) => {
  const { t } = useTranslation();

  return (
    <BaseChip
      className={cn('bg-neutral-950 px-2 py-0 text-[0.65rem] light:border-neutral-500', className)}
    >
      <LuLayers className="size-3.5" />
      {t('Subset')}
    </BaseChip>
  );
};
