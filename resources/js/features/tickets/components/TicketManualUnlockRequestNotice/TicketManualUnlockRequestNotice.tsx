import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildManualUnlockRequestUrl } from '@/common/utils/buildManualUnlockRequestUrl';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

export const TicketManualUnlockRequestNotice: FC = () => {
  const { achievement, shouldOfferManualUnlock, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  if (!achievement || !shouldOfferManualUnlock) {
    return null;
  }

  const manualUnlockRequestUrl = buildManualUnlockRequestUrl(
    { ...achievement, game: ticket.game },
    ticket.id,
  );

  return (
    <BaseAlert variant="notice" className="bg-embed light:bg-neutral-50">
      <BaseAlertTitle className="font-bold">{t('You can request a manual unlock.')}</BaseAlertTitle>

      <BaseAlertDescription className="flex flex-col items-start gap-3">
        <p>
          {t(
            "You'll need proof you should've been granted this unlock, such as a screenshot or a video.",
          )}
        </p>

        <a
          href={manualUnlockRequestUrl}
          className={cn(
            baseButtonVariants({ size: 'sm' }),
            buildTrackingClassNames('Click Ticket Request Manual Unlock'),
          )}
        >
          {t('Start a manual unlock request')}
        </a>
      </BaseAlertDescription>
    </BaseAlert>
  );
};
