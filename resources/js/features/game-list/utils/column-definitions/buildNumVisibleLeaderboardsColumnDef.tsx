import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildNumVisibleLeaderboardsColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildNumVisibleLeaderboardsColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildNumVisibleLeaderboardsColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'numVisibleLeaderboards',
    accessorKey: 'game',
    meta: { label: 'Leaderboards', align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const numVisibleLeaderboards = row.original.game?.numVisibleLeaderboards ?? 0;

      return (
        <p className={numVisibleLeaderboards === 0 ? 'text-muted' : ''}>
          {formatNumber(numVisibleLeaderboards)}
        </p>
      );
    },
  };
}
