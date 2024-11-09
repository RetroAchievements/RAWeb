import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

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
    meta: { t_label, align: 'right', sortType: 'quantity', Icon: gameListFieldIconMap.retroRatio },

    header: ({ column, table }) => (
      <DataTableColumnHeader column={column} table={table} tableApiRouteName={tableApiRouteName} />
    ),

    cell: ({ row }) => {
      const pointsTotal = row.original.game?.pointsTotal ?? 0;

      if (pointsTotal === 0) {
        return <p className="text-muted italic">{strings.t_none}</p>;
      }

      const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

      return <p>{buildGameRarityLabel(pointsTotal, pointsWeighted)}</p>;
    },
  };
}
