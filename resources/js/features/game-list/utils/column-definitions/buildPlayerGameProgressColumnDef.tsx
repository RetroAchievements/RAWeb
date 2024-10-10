import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildPlayerGameProgressColumnDefProps {
  t_label: string;

  tableApiRouteName?: RouteName;
}

export function buildPlayerGameProgressColumnDef({
  t_label,
  tableApiRouteName = 'api.game.index',
}: BuildPlayerGameProgressColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'progress',
    accessorKey: 'game',
    meta: { t_label, align: 'left' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const { game, playerGame } = row.original;

      return <PlayerGameProgressBar game={game} playerGame={playerGame} />;
    },
  };
}
