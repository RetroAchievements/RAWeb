import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

const emulatorSupportDocsHref =
  'https://docs.retroachievements.org/general/emulator-support-and-issues.html';

export const TicketBlockedNotice: FC = () => {
  const { ticketBlockReason } = usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();
  const { t } = useTranslation();

  if (!ticketBlockReason) {
    return null;
  }

  const isMissingPlaySession = ticketBlockReason === 'no_play_session';

  let message: TranslatedString;
  if (isMissingPlaySession) {
    message = t(
      'This account has no record of a play session for this game. To open a ticket, play the game with a supported emulator or client that is logged in to your account.',
    );
  } else {
    message = t(
      'Tickets are not available for this game. You can still send a message to a team below.',
    );
  }

  return (
    <li className="flex w-full flex-col gap-1 rounded-sm bg-embed px-3 py-2">
      <p className="text-neutral-300 light:text-neutral-900">{message}</p>

      {isMissingPlaySession ? (
        <a href={emulatorSupportDocsHref} target="_blank" rel="noreferrer">
          {t('Read about emulator support')}
        </a>
      ) : null}
    </li>
  );
};
