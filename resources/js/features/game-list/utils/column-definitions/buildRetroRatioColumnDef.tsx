import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildRetroRatioColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildRetroRatioColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildRetroRatioColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'retroRatio',
    accessorKey: 'game',
    meta: { label: 'Rarity', align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const pointsTotal = row.original.game?.pointsTotal ?? 0;

      if (pointsTotal === 0) {
        return <p className="text-muted italic">none</p>;
      }

      const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

      const result = pointsWeighted / pointsTotal;

      return <p>&times;{(Math.round((result + Number.EPSILON) * 100) / 100).toFixed(2)}</p>;
    },
  };
}
