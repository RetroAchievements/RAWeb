import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseCard, BaseCardContent } from '@/common/components/+vendor/BaseCard';
import { InertiaLink } from '@/common/components/InertiaLink';

export const UnsubscribeUndoSuccessCard: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseCard>
      <BaseCardContent className="flex flex-col gap-8 pt-8 text-center">
        <div className="flex flex-col gap-3">
          <p className="text-balance sm:text-center">{t('Your subscription has been restored.')}</p>
          <p>{t('You will continue receiving notifications as before.')}</p>
        </div>

        <InertiaLink href={route('settings.show')} prefetch="desktop-hover-only">
          {t('Manage All Email Preferences')}
        </InertiaLink>
      </BaseCardContent>
    </BaseCard>
  );
};
