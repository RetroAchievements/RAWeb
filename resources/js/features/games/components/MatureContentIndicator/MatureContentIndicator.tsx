import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuEyeOff } from 'react-icons/lu';

export const MatureContentIndicator: FC = () => {
  const { t } = useTranslation();

  return (
    <div className="flex justify-center rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
      <div className="flex w-full items-center justify-center gap-1.5 rounded-lg bg-neutral-800 p-1.5 text-neutral-300 light:text-neutral-700">
        <LuEyeOff className="size-4" />
        <p>{t('Mature Content')}</p>
      </div>
    </div>
  );
};
