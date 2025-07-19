import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuSearch } from 'react-icons/lu';

/**
 * TODO previous searches? trending searches?
 */

export const BootState: FC = () => {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col items-center justify-center gap-3 text-sm">
      <LuSearch className="size-12 opacity-50" />

      <p className="text-balance">{t('Search for games, hubs, users, events, and achievements')}</p>
      <p className="text-xs">{t('Type at least 3 characters to begin')}</p>
    </div>
  );
};
