import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';

import { buildStructuredMessage } from './buildStructuredMessage';
import { ReportIssueOptionItem } from './ReportIssueOptionItem';
import { ReportIssueSection } from './ReportIssueSection';

export const MissingUnlockSection: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();
  const { t } = useTranslation();

  return (
    <ReportIssueSection
      t_heading={t('I earned this achievement, but it is missing from my profile')}
    >
      <ReportIssueOptionItem
        t_buttonText={t('Request Manual Unlock')}
        href={route('message-thread.create', {
          to: 'UnlockTeam',
          ...buildStructuredMessage(achievement, 'manual-unlock'),
        })}
        anchorClassName={buildTrackingClassNames('Click Request Manual Unlock')}
      >
        {t(
          'You need proof: a screenshot of the achievement popup, a video, or a later achievement.',
        )}
      </ReportIssueOptionItem>
    </ReportIssueSection>
  );
};
