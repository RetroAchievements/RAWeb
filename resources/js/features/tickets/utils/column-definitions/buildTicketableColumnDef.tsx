import type { FC } from 'react';
import { LuChartBar } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { GameTitle } from '@/common/components/GameTitle';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition } from '../../models';
import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildTicketableColumnDefProps {
  t_label: TranslatedString;
  shouldShowGameTitle: boolean;
}

export function buildTicketableColumnDef({
  t_label,
  shouldShowGameTitle,
}: BuildTicketableColumnDefProps): TicketListColumnDefinition {
  return {
    id: 'ticketable',
    meta: {
      t_label,
      responsiveClassName: 'flex min-w-[10em] flex-[2_1_0] items-center gap-[0.6em]',
    },

    cell: ({ row }) => (
      <TicketableCell entry={row.original} shouldShowGameTitle={shouldShowGameTitle} />
    ),
  };
}

interface TicketableCellProps {
  entry: App.Platform.Data.TicketListEntry;
  shouldShowGameTitle: boolean;
}

const TicketableCell: FC<TicketableCellProps> = ({ entry, shouldShowGameTitle }) => {
  const { cardTooltipProps } = useCardTooltip({
    dynamicType: 'achievement', // TODO leaderboards
    dynamicId: entry.ticketableId,
  });

  const { cardTooltipProps: gameCardTooltipProps } = useCardTooltip({
    dynamicType: 'game',
    dynamicId: entry.game.id,
  });

  const isAchievement = entry.ticketableType === 'achievement';

  return (
    <div className="flex min-w-0 items-center gap-2">
      {isAchievement && entry.ticketableBadgeUrl ? (
        <InertiaLink
          prefetch="desktop-hover-only"
          href={route('achievement.show', { achievement: entry.ticketableId })}
          className={cn('flex-none', ticketListCellClassNames.entityLinkWrapper)}
          {...cardTooltipProps}
        >
          <img
            loading="lazy"
            decoding="async"
            width={24}
            height={24}
            src={entry.ticketableBadgeUrl}
            alt=""
            className="rounded-xs"
          />
        </InertiaLink>
      ) : null}

      {!isAchievement ? (
        <LuChartBar aria-hidden="true" className="size-6 flex-none text-link" />
      ) : null}

      <div className="flex min-w-0 flex-col">
        {isAchievement ? (
          <InertiaLink
            prefetch="desktop-hover-only"
            href={route('achievement.show', { achievement: entry.ticketableId })}
            className={cn(
              ticketListCellClassNames.entityLinkWrapper,
              ticketListCellClassNames.truncate,
            )}
            {...cardTooltipProps}
          >
            <span className={cn(ticketListCellClassNames.entityLinkLabel, 'text-neutral-300')}>
              {entry.ticketableTitle}
            </span>
          </InertiaLink>
        ) : (
          <a
            href={route('leaderboard.show', { leaderboard: entry.ticketableId })}
            className={cn(
              ticketListCellClassNames.entityLinkWrapper,
              ticketListCellClassNames.truncate,
            )}
          >
            <span className={cn(ticketListCellClassNames.entityLinkLabel, 'text-neutral-300')}>
              {entry.ticketableTitle}
            </span>
          </a>
        )}

        {shouldShowGameTitle ? (
          <InertiaLink
            prefetch="desktop-hover-only"
            href={route('game.show', { game: entry.game.id })}
            {...gameCardTooltipProps}
            className={cn(
              'truncate text-xs leading-tight text-neutral-400/90 hover:text-link light:text-neutral-600',
              ticketListCellClassNames.entityLinkWrapper,
            )}
          >
            <GameTitle title={entry.game.title} />
          </InertiaLink>
        ) : null}
      </div>
    </div>
  );
};
