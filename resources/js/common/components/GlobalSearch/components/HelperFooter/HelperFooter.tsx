import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { cn } from '@/common/utils/cn';

export const HelperFooter: FC = () => {
  const { t } = useTranslation();

  const kbdClassNames = cn([
    'flex h-5 items-center gap-1 rounded border border-neutral-700 bg-neutral-800 px-1.5',
    'font-mono font-medium text-neutral-400 light:border-neutral-300 light:bg-white light:text-neutral-600',
  ]);

  return (
    <div>
      <BaseSeparator className="light:bg-neutral-200" />

      <div className="flex w-full items-center justify-between p-3">
        <div>
          <div className="hidden items-center gap-4 sm:flex">
            <span className="flex items-center gap-1 text-neutral-500">
              <kbd className={kbdClassNames}>{'↑↓'}</kbd>
              {t('Navigate')}
            </span>

            <span className="flex items-center gap-1 text-neutral-500">
              <kbd className={kbdClassNames}>{'↵'}</kbd>
              {t('Select')}
            </span>

            <span className="flex items-center gap-1 text-neutral-500">
              <kbd className={kbdClassNames}>{'esc'}</kbd>
              {t('Close')}
            </span>
          </div>
        </div>

        <img src="/assets/images/ra-icon.webp" alt={'ra icon'} width={28} height={28}></img>
      </div>
    </div>
  );
};
