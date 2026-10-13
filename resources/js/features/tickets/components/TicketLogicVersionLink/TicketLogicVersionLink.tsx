import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildAchievementLogicHref } from '@/common/utils/buildAchievementLogicHref';

interface TicketLogicVersionLinkProps {
  achievementId: number;
  version: number;
}

export const TicketLogicVersionLink: FC<TicketLogicVersionLinkProps> = ({
  achievementId,
  version,
}) => {
  const { can } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const versionTag = t('v{{version}}', { version });

  if (!can.viewAchievementLogic) {
    return <span>{versionTag}</span>;
  }

  return <a href={buildAchievementLogicHref(achievementId, version)}>{versionTag}</a>;
};
