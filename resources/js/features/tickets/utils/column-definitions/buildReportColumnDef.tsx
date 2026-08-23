import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition } from '../../models';
import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildReportColumnDefProps {
  t_label: TranslatedString;
  ticketTypeLabels: Record<App.Community.Enums.TicketType, string>;
}

export function buildReportColumnDef({
  t_label,
  ticketTypeLabels,
}: BuildReportColumnDefProps): TicketListColumnDefinition {
  return {
    id: 'report',
    meta: {
      t_label,
      responsiveClassName: cn('flex items-center', 'min-w-[10em] flex-[3_1_0]'),
    },

    cell: ({ row }) => (
      <span className={cn('text-text', ticketListCellClassNames.truncate)}>
        {row.original.reportExcerpt || ticketTypeLabels[row.original.type]}
      </span>
    ),
  };
}
