import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildMasteryTimeColumnDefProps<TEntry> {
  t_label: TranslatedString;

  options?: Partial<ColumnDef<TEntry>>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildMasteryTimeColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  options,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildMasteryTimeColumnDefProps<TEntry>): ColumnDef<TEntry> {
  return {
    id: 'medianTimeToMasterHardcore',
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
      const { formatDuration } = useFormatDuration();

      const coreSetTimesCompletedHardcore =
        row.original.gameListStats?.coreSetTimesCompletedHardcore ?? 0;

      return (
        <p>
          {coreSetTimesCompletedHardcore < 5 ? (
            <span className="text-muted">{'-'}</span>
          ) : (
            formatDuration(row.original.gameListStats!.coreSetMedianTimeToCompleteHardcore, {
              shouldTruncateSeconds: true,
            })
          )}
        </p>
      );
    },

    ...options,
  };
}
