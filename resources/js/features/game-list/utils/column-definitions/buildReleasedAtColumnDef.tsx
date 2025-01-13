import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildReleasedAtColumnDefProps<TEntry> {
  t_label: TranslatedString;
  strings: { t_unknown: TranslatedString };

  options?: Partial<ColumnDef<TEntry>>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildReleasedAtColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  options,
  strings,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildReleasedAtColumnDefProps<TEntry>): ColumnDef<TEntry> {
  return {
    id: 'releasedAt',
    accessorKey: 'game',
    meta: { t_label, sortType: 'date', Icon: gameListFieldIconMap.releasedAt },

    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      const date = row.original.game?.releasedAt ?? null;
      const granularity = row.original.game?.releasedAtGranularity ?? 'day';

      if (!date) {
        return <p className="text-muted italic">{strings.t_unknown}</p>;
      }

      return <p>{formatGameReleasedAt(date, granularity)}</p>;
    },

    ...options,
  };
}
