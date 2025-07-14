import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildNumRequestsColumnDefProps<TEntry> {
  t_label: TranslatedString;

  options?: Partial<ColumnDef<TEntry>>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildNumRequestsColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  options,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildNumRequestsColumnDefProps<TEntry>): ColumnDef<TEntry> {
  return {
    id: 'numRequests',
    accessorKey: 'numRequests',
    meta: {
      t_label,
      align: 'right',
      sortType: 'quantity',
      Icon: gameListFieldIconMap.numRequests,
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
      const { formatNumber } = useFormatNumber();

      const numRequests = row.original.game?.numRequests ?? 0;
      const gameId = row.original.game?.id;

      if (numRequests === 0) {
        return <p className="text-muted">{formatNumber(numRequests)}</p>;
      }

      return (
        <a href={`/setRequestors.php?g=${gameId}`} className="transition hover:text-link">
          {formatNumber(numRequests)}
        </a>
      );
    },

    ...options,
  };
}
