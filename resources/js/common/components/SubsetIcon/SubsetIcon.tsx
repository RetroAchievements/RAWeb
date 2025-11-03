import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuLayers } from 'react-icons/lu';

export const SubsetIcon: FC = () => {
  const { t } = useTranslation();

  return (
    <>
      <LuLayers role="img" className="size-4" title={t('Subset')} aria-label={t('Subset')} />
      <span className="sr-only">{t('Subset')}</span>
    </>
  );
};
