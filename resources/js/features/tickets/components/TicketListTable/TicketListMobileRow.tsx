import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

import { ticketListCellClassNames } from '../../utils/column-definitions/ticketListCellClassNames';
import { TicketStateGlyph } from '../TicketStateGlyph';

interface TicketListMobileRowProps {
  entry: App.Platform.Data.TicketListEntry;
}

export const TicketListMobileRow: FC<TicketListMobileRowProps> = ({ entry }) => {
  const { t } = useTranslation();

  const { diffForHumans } = useDiffForHumans();

  return (
    <div className="flex w-full min-w-0 items-center gap-2 sm:hidden">
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

      <span className="min-w-0 truncate text-link">
        {entry.ticketableType === 'leaderboard'
          ? t('(LB) {{title}}', { title: entry.ticketableTitle })
          : entry.ticketableTitle}
      </span>

      <div className="ml-auto flex flex-none items-center gap-2">
        {entry.reporter ? (
          <img
            loading="lazy"
            decoding="async"
            width={16}
            height={16}
            src={entry.reporter.avatarUrl}
            alt={entry.reporter.avatarUrl}
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
