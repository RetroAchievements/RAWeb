import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuLayers } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

interface SubsetIconProps {
  className?: string;
}

export const SubsetIcon: FC<SubsetIconProps> = ({ className }) => {
  const { t } = useTranslation();

  return (
    <>
      <LuLayers
        role="img"
        className={cn('size-4', className)}
        title={t('Subset')}
        aria-label={t('Subset')}
      />
      <span className="sr-only">{t('Subset')}</span>
    </>
  );
};
