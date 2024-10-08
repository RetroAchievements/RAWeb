import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildNumUnresolvedTicketsColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildNumUnresolvedTicketsColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildNumUnresolvedTicketsColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'numUnresolvedTickets',
    accessorKey: 'game',
    meta: { label: 'Tickets', align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const numUnresolvedTickets = row.original.game?.numUnresolvedTickets ?? 0;
      const gameId = row.original.game?.id ?? 0;

      return (
        <a
          href={route('game.tickets', { game: gameId, 'filter[achievement]': 'core' })}
          className={numUnresolvedTickets === 0 ? 'text-muted' : ''}
        >
          {formatNumber(numUnresolvedTickets)}
        </a>
      );
    },
  };
}
