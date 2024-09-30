import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildPlayersTotalColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildPlayersTotalColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildPlayersTotalColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'playersTotal',
    accessorKey: 'game',
    meta: { label: 'Players', align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const playersTotal = row.original.game?.playersTotal ?? 0;

      return <p className={playersTotal === 0 ? 'text-muted' : ''}>{formatNumber(playersTotal)}</p>;
    },
  };
}
