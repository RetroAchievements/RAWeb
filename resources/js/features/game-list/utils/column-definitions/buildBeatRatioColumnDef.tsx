import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import type { TranslatedString } from '@/types/i18next';

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
      // eslint-disable-next-line react-hooks/rules-of-hooks -- the cell component is a FC. using this hook doesn't break the rules of hooks.
      const { formatPercentage } = useFormatPercentage();

      const playersHardcore = row.original.game.playersHardcore ?? 0;
      const beatRatio =
        playersHardcore > 0 ? (row.original.game.timesBeatenHardcore ?? 0) / playersHardcore : 0.0;

      return (
        <p className={playersHardcore === 0 ? 'text-muted' : ''}>
          {formatPercentage(beatRatio, { minimumFractionDigits: 1, maximumFractionDigits: 1 })}
        </p>
      );
    },

    ...options,
  };
}
