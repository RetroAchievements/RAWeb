import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildBeatTimeColumnDefProps<TEntry> {
  t_label: TranslatedString;

  options?: Partial<ColumnDef<TEntry>>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildBeatTimeColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  options,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildBeatTimeColumnDefProps<TEntry>): ColumnDef<TEntry> {
  return {
    id: 'medianTimeToBeatHardcore',
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
      const { formatDuration } = useFormatDuration();

      const timesBeatenHardcore = row.original.game?.timesBeatenHardcore ?? 0;
      const medianTimeToBeat =
        timesBeatenHardcore < 5 ? 0 : (row.original.game?.medianTimeToBeatHardcore ?? 0);

      return (
        <p>
          {medianTimeToBeat ? (
            formatDuration(medianTimeToBeat, { shouldTruncateSeconds: true })
          ) : (
            <span className="text-muted">{'--'}</span>
          )}
        </p>
      );
    },

    ...options,
  };
}
