import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildNumUnresolvedTicketsColumnDefProps {
  t_label: string;

  tableApiRouteName?: RouteName;
}

export function buildNumUnresolvedTicketsColumnDef({
  t_label,
  tableApiRouteName = 'api.game.index',
}: BuildNumUnresolvedTicketsColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'numUnresolvedTickets',
    accessorKey: 'game',
    meta: { t_label, align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      // eslint-disable-next-line react-hooks/rules-of-hooks -- the cell component is a FC. using this hook doesn't break the rules of hooks.
      const { formatNumber } = useFormatNumber();

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
