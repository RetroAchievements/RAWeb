import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck } from 'react-icons/lu';

import {
  BaseCard,
  BaseCardDescription,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';

import { OAuthPageLayout } from '../../OAuthPageLayout';

export const DeviceAuthorizationSuccess: FC = () => {
  const { t } = useTranslation();

  return (
    <OAuthPageLayout glowVariant="success">
      <BaseCard className="rounded-2xl p-8 shadow-lg shadow-black/20 ring-1 ring-white/5">
        <BaseCardHeader className="text-balance px-0 pb-0 pt-0 text-center">
          <div className="mb-6 flex justify-center">
            <div className="rounded-xl bg-green-500/10 p-3">
              <LuCheck className="size-8 text-green-500" />
            </div>
          </div>

          <BaseCardTitle className="text-neutral-300 light:text-neutral-900">
            {t('Authorized')}
          </BaseCardTitle>
          <BaseCardDescription className="text-neutral-500 light:text-neutral-700">
            {t('You can close this window.')}
          </BaseCardDescription>
        </BaseCardHeader>
      </BaseCard>
    </OAuthPageLayout>
  );
};
