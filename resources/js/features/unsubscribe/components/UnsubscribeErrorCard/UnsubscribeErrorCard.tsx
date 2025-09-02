import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { BaseCard, BaseCardContent } from '@/common/components/+vendor/BaseCard';
import { usePageProps } from '@/common/hooks/usePageProps';

export const UnsubscribeErrorCard: FC = () => {
  const { error } = usePageProps<App.Community.Data.UnsubscribeShowPageProps>();
  const { t } = useTranslation();

  return (
    <BaseCard>
      <BaseCardContent className="flex flex-col gap-8 pt-8 text-center">
        <p>{t('Unable to Unsubscribe')}</p>

        {/* eslint-disable-next-line @typescript-eslint/no-explicit-any -- this is fully dynamic */}
        {error ? t(`unsubscribeError-${error}` as unknown as any) : null}

        <a
          href={route('settings.show')}
          className={baseButtonVariants({ variant: 'link', size: 'sm' })}
        >
          {t('Go to Settings')}
        </a>
      </BaseCardContent>
    </BaseCard>
  );
};
