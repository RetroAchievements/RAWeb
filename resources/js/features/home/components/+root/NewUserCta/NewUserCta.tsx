import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuAlertCircle } from 'react-icons/lu';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';

export const NewUserCta: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseAlert variant="notice" className="bg-embed light:bg-neutral-50">
      <LuAlertCircle className="size-5" />
      <BaseAlertTitle className="mb-3 text-xl font-semibold leading-4">
        {t('Getting Started')}
      </BaseAlertTitle>

      <BaseAlertDescription>
        <Trans
          i18nKey="We're excited to have you here! We know you might have some questions about hardcore mode, RetroPoints (white points), subsets, or which emulators to use. Don't worry, we've got you covered! Check out our <1>comprehensive FAQ</1> to get started. Happy gaming!"
          components={{
            1: (
              <a
                href="https://docs.retroachievements.org/general/faq.html"
                target="_blank"
                className={buildTrackingClassNames('Click Home FAQ Link')}
              />
            ),
          }}
        />
      </BaseAlertDescription>
    </BaseAlert>
  );
};
