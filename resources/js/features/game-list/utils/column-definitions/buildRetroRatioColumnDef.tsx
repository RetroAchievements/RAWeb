import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildRetroRatioColumnDefProps {
  t_label: string;
  strings: { t_none: string };

  tableApiRouteName?: RouteName;
}

export function buildRetroRatioColumnDef({
  t_label,
  strings,
  tableApiRouteName = 'api.game.index',
}: BuildRetroRatioColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'retroRatio',
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
      const pointsTotal = row.original.game?.pointsTotal ?? 0;

      if (pointsTotal === 0) {
        return <p className="text-muted italic">{strings.t_none}</p>;
      }

      const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

      const result = pointsWeighted / pointsTotal;

      // eslint-disable-next-line react/jsx-no-literals -- this is valid
      return <p>&times;{(Math.round((result + Number.EPSILON) * 100) / 100).toFixed(2)}</p>;
    },
  };
}
