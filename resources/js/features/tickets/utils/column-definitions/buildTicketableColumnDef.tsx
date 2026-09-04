import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition } from '../../models';
import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildTicketableColumnDefProps {
  t_label: TranslatedString;
}

export function buildTicketableColumnDef({
  t_label,
}: BuildTicketableColumnDefProps): TicketListColumnDefinition {
  return {
    id: 'ticketable',
    meta: {
      t_label,
      responsiveClassName: 'flex min-w-[10em] flex-[2_1_0] items-center gap-[0.6em]',
    },

    cell: ({ row }) => <TicketableCell entry={row.original} />,
  };
}

interface TicketableCellProps {
  entry: App.Platform.Data.TicketListEntry;
}

const TicketableCell: FC<TicketableCellProps> = ({ entry }) => {
  const { t } = useTranslation();

  if (entry.ticketableType === 'leaderboard') {
    return (
      <span className={cn(ticketListCellClassNames.dimText, ticketListCellClassNames.truncate)}>
        {t('(LB) {{title}}', { title: entry.ticketableTitle })}
      </span>
    );
  }

  const badgeUrl = entry.ticketableBadgeUrl;

  return (
    <a
      href={route('achievement.show', { achievement: entry.ticketableId })}
      className={cn(
        'flex max-w-full min-w-0 items-center gap-2',
        ticketListCellClassNames.entityLinkWrapper,
      )}
    >
      {badgeUrl ? (
        <img
          loading="lazy"
          decoding="async"
          width={24}
          height={24}
          src={badgeUrl}
          alt=""
          className="flex-none rounded-xs"
        />
      ) : null}
      <span
        className={cn(ticketListCellClassNames.entityLinkLabel, ticketListCellClassNames.truncate)}
      >
        {entry.ticketableTitle}
      </span>
    </a>
  );
};
