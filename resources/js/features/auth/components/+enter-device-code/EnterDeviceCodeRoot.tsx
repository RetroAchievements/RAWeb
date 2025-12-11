import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseCard,
  BaseCardDescription,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';
import { usePageProps } from '@/common/hooks/usePageProps';

import { EnterDeviceCodeForm } from '../EnterDeviceCodeForm';
import { OAuthPageLayout } from '../OAuthPageLayout';
import { DeviceAuthorizationDenied } from './DeviceAuthorizationDenied';
import { DeviceAuthorizationSuccess } from './DeviceAuthorizationSuccess';

export const EnterDeviceCodeRoot: FC = () => {
  const { errors, flash } = usePageProps<App.Data.EnterDeviceCodePageProps>();
  const { t } = useTranslation();

  // Skip animations when returning with an error (indicates a redirect back).
  const hasError = !!errors?.user_code;

  const isSuccess = flash?.status === 'authorization-approved';
  const isDenied = flash?.status === 'authorization-denied';

  if (isSuccess) {
    return <DeviceAuthorizationSuccess />;
  }

  if (isDenied) {
    return <DeviceAuthorizationDenied />;
  }

  return (
    <OAuthPageLayout initial={hasError ? false : { opacity: 0, y: 12 }}>
      <BaseCard className="rounded-2xl p-8 shadow-lg shadow-black/20 ring-1 ring-white/5">
        <BaseCardHeader className="text-balance px-0 pt-0 text-center">
          <BaseCardTitle className="text-neutral-300 light:text-neutral-900">
            {t('Link your app')}
          </BaseCardTitle>
          <BaseCardDescription className="text-neutral-500 light:text-neutral-700">
            {t('Enter the code shown in the app you want to connect to RetroAchievements.')}
          </BaseCardDescription>
        </BaseCardHeader>

        <EnterDeviceCodeForm serverError={errors?.user_code} />
      </BaseCard>
    </OAuthPageLayout>
  );
};
