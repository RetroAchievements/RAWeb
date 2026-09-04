import { GameAvatar } from '@/common/components/GameAvatar';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition } from '../../models';
import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildGameColumnDefProps {
  t_label: TranslatedString;
}

export function buildGameColumnDef({
  t_label,
}: BuildGameColumnDefProps): TicketListColumnDefinition {
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
            wrapperClassName={cn(
              'min-w-0 flex-1 overflow-hidden',
              ticketListCellClassNames.entityLinkWrapper,
            )}
            gameTitleClassName={cn(
              ticketListCellClassNames.entityLinkLabel,
              ticketListCellClassNames.truncate,
            )}
          />

          <span className={cn(ticketListCellClassNames.dimText, 'shrink-0')}>
            {'·'} {game.system!.nameShort}
          </span>
        </div>
      );
    },
  };
}
