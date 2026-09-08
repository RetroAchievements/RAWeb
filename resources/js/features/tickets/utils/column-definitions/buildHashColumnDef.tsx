import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition } from '../../models';
import { getGameHashDisplayLabel } from '../getGameHashDisplayLabel';
import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildHashColumnDefProps {
  t_label: TranslatedString;
}

export function buildHashColumnDef({
  t_label,
}: BuildHashColumnDefProps): TicketListColumnDefinition {
  return {
    id: 'hash',
    meta: {
      t_label,
      responsiveClassName: cn('flex items-center', 'w-[10em] flex-none'),
    },

    cell: ({ row }) => {
      const { gameHash } = row.original;
      if (!gameHash) {
        return null;
      }

      return (
        <BaseTooltip>
          <BaseTooltipTrigger asChild>
            <span
              className={cn(
                'relative z-10',
                ticketListCellClassNames.dimText,
                ticketListCellClassNames.truncate,
              )}
            >
              {getGameHashDisplayLabel(gameHash)}
            </span>
          </BaseTooltipTrigger>

          <BaseTooltipContent>
            <div className="flex flex-col gap-0.5">
              {gameHash.name ? <span>{gameHash.name}</span> : null}
              <span className="font-mono text-xs">{gameHash.md5}</span>
            </div>
          </BaseTooltipContent>
        </BaseTooltip>
      );
    },
  };
}
