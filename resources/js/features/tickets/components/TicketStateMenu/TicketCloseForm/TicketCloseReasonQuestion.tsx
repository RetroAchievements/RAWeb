import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import {
  TICKET_CLOSE_REASON_LABELS,
  type TicketCloseAction,
} from '../../../utils/ticketCloseReasonLabels';
import { TicketCloseQuestion } from './TicketCloseQuestion';

interface TicketCloseReasonQuestionProps {
  actions: TicketCloseAction[];
  isTicketablePromoted: boolean;
  onValueChange: (value: string) => void;
  value: TicketCloseAction | null;
}

export const TicketCloseReasonQuestion: FC<TicketCloseReasonQuestionProps> = ({
  actions,
  isTicketablePromoted,
  onValueChange,
  value,
}) => {
  const { ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const isAchievementTicket = ticket.ticketableType === 'achievement';

  const getReasonLabel = (closeAction: TicketCloseAction): TranslatedString => {
    if (closeAction !== 'demoted') {
      return t(TICKET_CLOSE_REASON_LABELS[closeAction]);
    }

    if (isAchievementTicket) {
      return isTicketablePromoted ? t("I'm demoting it") : t('It was already demoted');
    }

    return isTicketablePromoted ? t("I'm unpromoting it") : t('It was already unpromoted');
  };

  // Hidden hints still occupy visual space to mitigate any possibility of a layout shift.
  const renderInlineHint = (closeAction: TicketCloseAction, hintText: TranslatedString) => (
    <p
      aria-hidden={value !== closeAction}
      className={cn(
        'text-xs text-neutral-400 light:text-neutral-600',
        value !== closeAction && 'invisible',
      )}
    >
      {hintText}
    </p>
  );

  const demotionHint = isAchievementTicket
    ? t('Submitting will demote this achievement.')
    : t('Submitting will unpromote this leaderboard.');

  return (
    <TicketCloseQuestion
      label={t('What happened?')}
      options={actions.map((closeAction) => ({
        label: getReasonLabel(closeAction),
        value: closeAction,
      }))}
      value={value}
      onValueChange={onValueChange}
    >
      {actions.includes('demoted') && isTicketablePromoted
        ? renderInlineHint('demoted', demotionHint)
        : null}

      {actions.includes('unable-to-debug')
        ? renderInlineHint(
            'unable-to-debug',
            t(
              'If the player gave steps to reproduce the problem, try to reproduce it before you close the ticket.',
            ),
          )
        : null}

      {isAchievementTicket && actions.includes('network')
        ? renderInlineHint(
            'network',
            t(
              "If the reporter still hasn't unlocked this achievement, they'll see a button to request a manual unlock.",
            ),
          )
        : null}
    </TicketCloseQuestion>
  );
};
