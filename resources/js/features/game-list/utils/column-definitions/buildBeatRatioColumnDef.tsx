import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import type { TranslatedString } from '@/types/i18next';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildBeatRatioColumnDefProps<TEntry> {
  t_label: TranslatedString;

  options?: Partial<ColumnDef<TEntry>>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildBeatRatioColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  options,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildBeatRatioColumnDefProps<TEntry>): ColumnDef<TEntry> {
  return {
    id: 'beatRatio',
    accessorKey: 'game',
    meta: { t_label, align: 'right', sortType: 'quantity', Icon: gameListFieldIconMap.timeToBeat },

    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      const { formatPercentage } = useFormatPercentage();
      
      const playersTotal = row.original.game?.playersTotal ?? 0;
      const beatRatio = playersTotal > 0 ? (row.original.game?.timesBeatenHardcore ?? 0) / playersTotal : 0.0;

      return <p className={playersTotal === 0 ? 'text-muted' : ''}>{formatPercentage(beatRatio)}</p>;
    },

    ...options,
  };
}
