import type { FC } from 'react';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';
import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { cn } from '@/common/utils/cn';

import { ticketListCellClassNames } from '../../utils/column-definitions/ticketListCellClassNames';

interface TicketableTitleLinkProps {
  entry: App.Platform.Data.TicketListEntry;
  labelClassName: string;
}

export const TicketableTitleLink: FC<TicketableTitleLinkProps> = ({ entry, labelClassName }) => {
  const { cardTooltipProps } = useCardTooltip({
    dynamicType: 'achievement', // TODO 'leaderboard'
    dynamicId: entry.ticketableId,
  });

  const linkClassName = cn(
    ticketListCellClassNames.entityLinkWrapper,
    ticketListCellClassNames.truncate,
  );
  const label = (
    <span className={cn(ticketListCellClassNames.entityLinkLabel, labelClassName)}>
      {entry.ticketableTitle}
    </span>
  );

  if (entry.ticketableType === 'leaderboard') {
    return (
      <a
        href={route('leaderboard.show', { leaderboard: entry.ticketableId })}
        className={linkClassName}
      >
        {label}
      </a>
    );
  }

  return (
    <InertiaLink
      prefetch="desktop-hover-only"
      href={route('achievement.show', { achievement: entry.ticketableId })}
      className={linkClassName}
      {...cardTooltipProps}
    >
      {label}
    </InertiaLink>
  );
};
