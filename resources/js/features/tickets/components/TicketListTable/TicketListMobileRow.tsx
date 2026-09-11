import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { GameTitle } from '@/common/components/GameTitle';
import { cn } from '@/common/utils/cn';
import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

import { ticketListCellClassNames } from '../../utils/column-definitions/ticketListCellClassNames';
import { TicketStateGlyph } from '../TicketStateGlyph';

interface TicketListMobileRowProps {
  entry: App.Platform.Data.TicketListEntry;

  /** Should be falsy for game-scoped lists, as it'd be redundant. */
  shouldShowGameTitle: boolean;
}

export const TicketListMobileRow: FC<TicketListMobileRowProps> = ({
  entry,
  shouldShowGameTitle,
}) => {
  const { t } = useTranslation();

  const { diffForHumans } = useDiffForHumans();

  return (
    <div role="cell" className="flex w-full min-w-0 items-center gap-2 sm:hidden">
      <TicketStateGlyph state={entry.state} className="flex-none" />

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

      <div className="flex min-w-0 flex-1 flex-col">
        <span className="truncate text-link">
          {entry.ticketableType === 'leaderboard'
            ? t('(LB) {{title}}', { title: entry.ticketableTitle })
            : entry.ticketableTitle}
        </span>

        {shouldShowGameTitle ? (
          <GameTitle
            title={entry.game.title}
            className={cn('truncate text-xs', ticketListCellClassNames.dimText)}
          />
        ) : null}
      </div>

      <div className="ml-auto flex flex-none items-center gap-2">
        {entry.reporter ? (
          <img
            loading="lazy"
            decoding="async"
            width={16}
            height={16}
            src={entry.reporter.avatarUrl}
            alt="" // empty string is actually a sentinel value for assistive devices
            className="rounded-xs"
          />
        ) : null}

        <span className={ticketListCellClassNames.dimText} suppressHydrationWarning={true}>
          {diffForHumans(entry.createdAt, { style: 'narrow' })}
        </span>
      </div>
    </div>
  );
};
