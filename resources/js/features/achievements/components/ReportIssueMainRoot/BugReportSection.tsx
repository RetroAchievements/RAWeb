import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';

import { ReportIssueOptionItem } from './ReportIssueOptionItem';
import { ReportIssueSection } from './ReportIssueSection';
import { TicketBlockedNotice } from './TicketBlockedNotice';

export const BugReportSection: FC = () => {
  const { achievement, can, ticketType, extra } =
    usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();
  const { t } = useTranslation();

  return (
    <ReportIssueSection
      t_heading={t('The achievement has a bug')}
      t_description={t(
        'A ticket tells the developer about the bug. It does not add the achievement to your profile.',
      )}
    >
      <TicketBlockedNotice />

      {can.createTicket && ticketType === 'did_not_trigger' ? (
        <>
          <ReportIssueOptionItem
            t_buttonText={t('Create Ticket')}
            href={route('achievement.tickets.create', {
              achievement: achievement.id,
              type: 'did_not_trigger',
            })}
            anchorClassName={buildTrackingClassNames('Click Create Ticket')}
            shouldUseClientSideRoute={true}
          >
            {t('I met the requirements, but the achievement did not trigger.')}
          </ReportIssueOptionItem>

          <ReportIssueOptionItem
            t_buttonText={t('Create Ticket')}
            href={route('achievement.tickets.create', {
              achievement: achievement.id,
              type: 'triggered_at_wrong_time',
              extra,
            })}
            anchorClassName={buildTrackingClassNames('Click Create Ticket')}
            shouldUseClientSideRoute={true}
          >
            {t(
              'I unlocked this achievement without meeting the requirements, and then I reset it.',
            )}
          </ReportIssueOptionItem>
        </>
      ) : null}

      {can.createTicket && ticketType !== 'did_not_trigger' ? (
        <>
          <ReportIssueOptionItem
            t_buttonText={t('Create Ticket')}
            href={route('achievement.tickets.create', {
              achievement: achievement.id,
              type: 'triggered_at_wrong_time',
              extra,
            })}
            anchorClassName={buildTrackingClassNames('Click Create Ticket')}
            shouldUseClientSideRoute={true}
          >
            {t('I unlocked this achievement without meeting the requirements.')}
          </ReportIssueOptionItem>

          <ReportIssueOptionItem
            t_buttonText={t('Create Ticket')}
            href={route('achievement.tickets.create', {
              achievement: achievement.id,
              type: 'did_not_trigger',
            })}
            anchorClassName={buildTrackingClassNames('Click Create Ticket')}
            shouldUseClientSideRoute={true}
          >
            {t('I met the requirements, but the achievement did not trigger.')}{' '}
            {t('It did trigger on a later attempt.')}
          </ReportIssueOptionItem>
        </>
      ) : null}
    </ReportIssueSection>
  );
};
