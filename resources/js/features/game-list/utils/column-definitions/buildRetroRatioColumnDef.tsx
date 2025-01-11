import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildRetroRatioColumnDefProps<TEntry> {
  t_label: TranslatedString;
  strings: { t_none: TranslatedString };

  options?: Partial<ColumnDef<TEntry>>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildRetroRatioColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  options,
  strings,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildRetroRatioColumnDefProps<TEntry>): ColumnDef<TEntry> {
  return {
    id: 'retroRatio',
    accessorKey: 'game',
    meta: { t_label, align: 'right', sortType: 'quantity', Icon: gameListFieldIconMap.retroRatio },

    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      const pointsTotal = row.original.game?.pointsTotal ?? 0;

      if (pointsTotal === 0) {
        return <p className="text-muted italic">{strings.t_none}</p>;
      }

      const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

      return <p>{buildGameRarityLabel(pointsTotal, pointsWeighted)}</p>;
    },

    ...options,
  };
}
