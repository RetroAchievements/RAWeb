import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildMasteryRatioColumnDefProps<TEntry> {
  t_label: TranslatedString;

  options?: Partial<ColumnDef<TEntry>>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildMasteryRatioColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  options,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildMasteryRatioColumnDefProps<TEntry>): ColumnDef<TEntry> {
  return {
    id: 'masteryRatio',
    accessorKey: 'game',
    meta: {
      t_label,
      align: 'right',
      sortType: 'quantity',
      Icon: gameListFieldIconMap.timeToBeat,
    },

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

      const coreSetTimesCompletedHardcore =
        row.original.gameListStats?.coreSetTimesCompletedHardcore ?? 0;
      const coreSetPlayersHardcore = row.original.gameListStats?.coreSetPlayersHardcore ?? 0;

      // Require a minimum number of masteries before showing a ratio.
      if (coreSetTimesCompletedHardcore < 5) {
        return <p className="text-muted">{'-'}</p>;
      }

      const masteryRatio = coreSetTimesCompletedHardcore / coreSetPlayersHardcore;

      return (
        <p>
          {formatPercentage(masteryRatio, { minimumFractionDigits: 1, maximumFractionDigits: 1 })}
        </p>
      );
    },

    ...options,
  };
}
