import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChartBar } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { GameTitle } from '@/common/components/GameTitle';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { cn } from '@/common/utils/cn';

import { ticketListCellClassNames } from '../../utils/column-definitions/ticketListCellClassNames';
import { TicketableTitleLink } from '../TicketableTitleLink';

const groupTitleClassName = 'font-semibold text-neutral-200 light:text-neutral-900';

interface TicketListGroupHeaderRowProps {
  columnCount: number;
  entry: App.Platform.Data.TicketListEntry;
  field: App.Platform.Enums.TicketListSortField;
  isContinued: boolean;
  ticketCount: number;
}

export const TicketListGroupHeaderRow: FC<TicketListGroupHeaderRowProps> = ({
  columnCount,
  entry,
  field,
  isContinued,
  ticketCount,
}) => {
  const { t } = useTranslation();

  const detailClassName = cn('whitespace-nowrap text-xs', ticketListCellClassNames.dimText);
  const ticketCountNode = (
    <>
      <span className={detailClassName}>
        {t('{{val, number}} tickets', { count: ticketCount, val: ticketCount })}
      </span>

      {isContinued ? (
        <span className={detailClassName}>{t('continued from previous page')}</span>
      ) : null}
    </>
  );

  return (
    <div
      role="row"
      data-testid="ticket-list-group-header"
      className={cn(
        'flex min-w-full items-center px-[0.6em] py-1.5 sm:w-min',
        'bg-neutral-950 light:bg-neutral-100 black:bg-neutral-900',
      )}
    >
      <div role="rowheader" aria-colspan={columnCount} className="flex min-w-0 items-center gap-2">
        {field === 'game' ? (
          <GameGroupLabel game={entry.game} ticketCountNode={ticketCountNode} />
        ) : (
          <TicketableGroupLabel entry={entry} ticketCountNode={ticketCountNode} />
        )}
      </div>
    </div>
  );
};

interface GroupTextProps {
  children: ReactNode;
}

const GroupText: FC<GroupTextProps> = ({ children }) => (
  <span className="flex min-w-0 items-baseline gap-2">{children}</span>
);

interface GameGroupLabelProps {
  game: App.Platform.Data.Game;
  ticketCountNode: ReactNode;
}

const GameGroupLabel: FC<GameGroupLabelProps> = ({ game, ticketCountNode }) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'game', dynamicId: game.id });

  return (
    <>
      {game.badgeUrl ? (
        <img
          loading="lazy"
          decoding="async"
          width={24}
          height={24}
          src={game.badgeUrl}
          alt=""
          className="flex-none rounded-xs"
        />
      ) : null}

      <GroupText>
        <InertiaLink
          prefetch="desktop-hover-only"
          href={route('game.tickets', { game: game.id })}
          className={cn(
            ticketListCellClassNames.entityLinkWrapper,
            ticketListCellClassNames.truncate,
          )}
          {...cardTooltipProps}
        >
          <GameTitle
            title={game.title}
            className={cn(ticketListCellClassNames.entityLinkLabel, groupTitleClassName)}
          />
        </InertiaLink>

        {game.system?.nameShort ? (
          <span className={cn('whitespace-nowrap text-xs', ticketListCellClassNames.dimText)}>
            {game.system.nameShort}
          </span>
        ) : null}

        {ticketCountNode}
      </GroupText>
    </>
  );
};

interface TicketableGroupLabelProps {
  entry: App.Platform.Data.TicketListEntry;
  ticketCountNode: ReactNode;
}

const TicketableGroupLabel: FC<TicketableGroupLabelProps> = ({ entry, ticketCountNode }) => (
  <>
    {entry.ticketableType === 'achievement' && entry.ticketableBadgeUrl ? (
      <img
        loading="lazy"
        decoding="async"
        width={24}
        height={24}
        src={entry.ticketableBadgeUrl}
        alt=""
        className="flex-none rounded-xs"
      />
    ) : null}

    {entry.ticketableType === 'leaderboard' ? (
      <LuChartBar aria-hidden="true" className="size-6 flex-none text-link" />
    ) : null}

    <GroupText>
      <TicketableTitleLink entry={entry} labelClassName={groupTitleClassName} />

      {ticketCountNode}
    </GroupText>
  </>
);
