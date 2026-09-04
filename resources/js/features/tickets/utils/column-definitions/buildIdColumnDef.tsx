import type { ColumnDef } from '@tanstack/react-table';
import { route } from 'ziggy-js';

import type { TranslatedString } from '@/types/i18next';

interface BuildIdColumnDefProps {
  t_label: TranslatedString;
}

export function buildIdColumnDef({
  t_label,
}: BuildIdColumnDefProps): ColumnDef<App.Platform.Data.TicketListEntry> {
  return {
    id: 'id',
    meta: {
      t_label,
      align: 'left',
      responsiveClassName: 'w-[4.4em] flex-none text-left tabular-nums',
    },

    cell: ({ row }) => (
      <a href={route('ticket.show', { ticket: row.original.id })} className="relative z-10">
        {row.original.id}
      </a>
    ),
  };
}
