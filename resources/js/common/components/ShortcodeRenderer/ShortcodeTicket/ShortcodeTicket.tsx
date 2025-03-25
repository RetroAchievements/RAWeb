import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { useCardTooltip } from '../../../hooks/useCardTooltip';
import { persistedTicketsAtom } from '../../../state/shortcode.atoms';
import { cn } from '../../../utils/cn';
import { TicketState } from '../../../utils/generatedAppConstants';

interface ShortcodeTicketProps {
  ticketId: number;
}

export const ShortcodeTicket: FC<ShortcodeTicketProps> = ({ ticketId }) => {
  const [persistedTickets] = useAtom(persistedTicketsAtom);

  const { t } = useTranslation();

  const foundTicket = persistedTickets?.find((ticket) => ticket.id === ticketId);

  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'ticket', dynamicId: ticketId });

  if (!foundTicket) {
    return null;
  }

  if (
    foundTicket.ticketableType === 'achievement' &&
    foundTicket.ticketable &&
    'badgeUnlockedUrl' in foundTicket.ticketable
  ) {
    return (
      <a
        data-testid="achievement-ticket-embed"
        href={route('ticket.show', { ticket: ticketId })}
        {...cardTooltipProps}
        className={cn('inline-block rounded', getTicketStateClassName(foundTicket.state ?? -1))}
      >
        <img
          loading="lazy"
          decoding="async"
          width={28}
          height={28}
          src={foundTicket.ticketable.badgeUnlockedUrl}
          alt={'ticket'}
          className="rounded-sm border-2 border-transparent"
        />

        <span className="px-1">{t('Ticket #{{ticketId}}', { ticketId })}</span>
      </a>
    );
  }

  // Other ticketable types aren't supported yet.
  return null;
};

function getTicketStateClassName(ticketState: number): string {
  // Match the state to return appropriate class name.
  if (ticketState === TicketState.Open || ticketState === TicketState.Request) {
    return 'border border-green-600';
  }

  if (ticketState === TicketState.Closed || ticketState === TicketState.Resolved) {
    return 'border border-red-600';
  }

  return '';
}
