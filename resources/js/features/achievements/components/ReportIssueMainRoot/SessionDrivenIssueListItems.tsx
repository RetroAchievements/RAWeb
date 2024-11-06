import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { TicketType } from '@/common/utils/generatedAppConstants';

import { buildStructuredMessage } from './buildStructuredMessage';
import { ReportIssueOptionItem } from './ReportIssueOptionItem';

export const SessionDrivenIssueListItems: FC = () => {
  const { achievement, hasSession, ticketType, extra } =
    usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();

  const { t } = useTranslation();

  // Don't allow the player to create tickets if they've never loaded the game.
  if (!hasSession) {
    return null;
  }

  if (ticketType === TicketType.DidNotTrigger) {
    return (
      <>
        <ReportIssueOptionItem
          t_buttonText={t('Create Ticket')}
          href={route('achievement.create-ticket', {
            achievement: achievement.id,
            type: TicketType.DidNotTrigger,
          })}
          anchorClassName={buildTrackingClassNames('Click Create Ticket')}
        >
          {t('I met the requirements, but the achievement did not trigger.')}
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          t_buttonText={t('Create Ticket')}
          href={route('achievement.create-ticket', {
            achievement: achievement.id,
            type: TicketType.TriggeredAtWrongTime,
            extra,
          })}
          anchorClassName={buildTrackingClassNames('Click Create Ticket')}
        >
          {t('I unlocked this achievement without meeting the requirements, and then I reset it.')}
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          t_buttonText={t('Request Manual Unlock')}
          href={route('message.create', {
            to: 'RAdmin',
            ...buildStructuredMessage(achievement, 'manual-unlock'),
          })}
          anchorClassName={buildTrackingClassNames('Click Request Manual Unlock')}
        >
          {t("The achievement triggered, but the unlock didn't appear on my profile.")}
        </ReportIssueOptionItem>
      </>
    );
  }

  return (
    <ReportIssueOptionItem
      t_buttonText={t('Create Ticket')}
      href={route('achievement.create-ticket', {
        achievement: achievement.id,
        type: TicketType.TriggeredAtWrongTime,
        extra,
      })}
      anchorClassName={buildTrackingClassNames('Click Create Ticket')}
    >
      {t('I unlocked this achievement without meeting the requirements.')}
    </ReportIssueOptionItem>
  );
};
