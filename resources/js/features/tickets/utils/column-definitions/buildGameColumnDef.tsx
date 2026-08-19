import type { ColumnDef } from '@tanstack/react-table';

import { GameAvatar } from '@/common/components/GameAvatar';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildGameColumnDefProps {
  t_label: TranslatedString;
}

export function buildGameColumnDef({
  t_label,
}: BuildGameColumnDefProps): ColumnDef<App.Platform.Data.TicketListEntry> {
  return {
    id: 'game',
    meta: {
      t_label,
      responsiveClassName: cn(
        'flex min-w-[10em] flex-[3_1_0] items-center gap-[0.6em]',
        ticketListCellClassNames.gameResponsive,
      ),
    },

    cell: ({ row }) => {
      const { game } = row.original;

      return (
        <div className="flex max-w-full min-w-0 items-center gap-2">
          <GameAvatar
            {...game}
            size={24}
            hasTooltip={false}
            wrapperClassName={cn('max-w-full min-w-0', ticketListCellClassNames.entityLinkWrapper)}
            gameTitleClassName={cn(
              ticketListCellClassNames.entityLinkLabel,
              ticketListCellClassNames.truncate,
            )}
          />

          {game.system?.nameShort ? (
            <span className={cn(ticketListCellClassNames.dimText, 'shrink-0')}>
              {'·'} {game.system.nameShort}
            </span>
          ) : null}
        </div>
      );
    },
  };
}
