import type { FC } from 'react';
import { Trans } from 'react-i18next';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';

interface TicketGameTicketsFactProps {
  gameId: number;
  resolvedCount: number;
  unresolvedCount: number;
}

export const TicketGameTicketsFact: FC<TicketGameTicketsFactProps> = ({
  gameId,
  resolvedCount,
  unresolvedCount,
}) => {
  return (
    <>
      <Trans
        i18nKey="gameOtherOpenTickets"
        count={unresolvedCount}
        components={{
          1: (
            <InertiaLink
              href={route('game.tickets', { game: gameId, filter: { status: 'unresolved' } })}
            />
          ),
        }}
      />

      {resolvedCount ? (
        <>
          {' '}
          <Trans
            i18nKey="otherResolvedTickets"
            count={resolvedCount}
            components={{
              1: (
                <InertiaLink
                  href={route('game.tickets', { game: gameId, filter: { status: 'resolved' } })}
                />
              ),
            }}
          />
        </>
      ) : null}
    </>
  );
};
