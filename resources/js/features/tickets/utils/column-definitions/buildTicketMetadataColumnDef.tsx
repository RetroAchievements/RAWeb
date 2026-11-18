import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition, TicketListColumnId } from '../../models';
import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildTicketMetadataColumnDefProps {
  getText: (entry: App.Platform.Data.TicketListEntry) => string | null;
  id: TicketListColumnId;
  t_label: TranslatedString;

  widthClassName?: string;
}

export function buildTicketMetadataColumnDef({
  getText,
  id,
  t_label,
  widthClassName = 'w-[9em] flex-none',
}: BuildTicketMetadataColumnDefProps): TicketListColumnDefinition {
  return {
    id,
    meta: {
      t_label,
      responsiveClassName: cn('flex items-center', widthClassName),
    },

    cell: ({ row }) => (
      <span className={cn(ticketListCellClassNames.dimText, ticketListCellClassNames.truncate)}>
        {getText(row.original)}
      </span>
    ),
  };
}
