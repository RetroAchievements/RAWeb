import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';
import { buildManualUnlockRequestUrl } from '@/features/achievements/utils/buildManualUnlockRequestUrl';

interface RequestManualUnlockLinkProps {
  ticketId?: number;
}

export const RequestManualUnlockLink: FC<RequestManualUnlockLinkProps> = ({ ticketId }) => {
  const { achievement } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();
  const { t } = useTranslation();

  return (
    <a
      href={buildManualUnlockRequestUrl(achievement, ticketId)}
      className={cn(
        baseButtonVariants({ size: 'sm' }),
        buildTrackingClassNames('Click Request Manual Unlock'),
      )}
    >
      {t('Request Manual Unlock')}
    </a>
  );
};
