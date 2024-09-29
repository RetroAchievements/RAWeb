import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { TicketType } from '@/common/utils/generatedAppConstants';

import { buildStructuredMessage } from './buildStructuredMessage';
import { ReportIssueOptionItem } from './ReportIssueOptionItem';

export const SessionDrivenIssueListItems: FC = () => {
  const { achievement, hasSession, ticketType, extra } =
    usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();

  // Don't allow the player to create tickets if they've never loaded the game.
  if (!hasSession) {
    return null;
  }

  if (ticketType === TicketType.DidNotTrigger) {
    return (
      <>
        <ReportIssueOptionItem
          buttonText="Create Ticket"
          href={route('achievement.create-ticket', {
            achievement: achievement.id,
            type: TicketType.DidNotTrigger,
          })}
          anchorClassName={buildTrackingClassNames('Click Create Ticket')}
        >
          I met the requirements, but the achievement did not trigger.
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          buttonText="Create Ticket"
          href={route('achievement.create-ticket', {
            achievement: achievement.id,
            type: TicketType.TriggeredAtWrongTime,
            extra,
          })}
          anchorClassName={buildTrackingClassNames('Click Create Ticket')}
        >
          I unlocked this achievement without meeting the requirements, and then I reset it.
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          buttonText="Request Manual Unlock"
          href={route('message.create', {
            to: 'RAdmin',
            ...buildStructuredMessage(achievement, 'manual-unlock'),
          })}
          anchorClassName={buildTrackingClassNames('Click Request Manual Unlock')}
        >
          The achievement triggered, but the unlock didn't appear on my profile.
        </ReportIssueOptionItem>
      </>
    );
  }

  return (
    <ReportIssueOptionItem
      buttonText="Create Ticket"
      href={route('achievement.create-ticket', {
        achievement: achievement.id,
        type: TicketType.TriggeredAtWrongTime,
        extra,
      })}
      anchorClassName={buildTrackingClassNames('Click Create Ticket')}
    >
      I unlocked this achievement without meeting the requirements.
    </ReportIssueOptionItem>
  );
};
