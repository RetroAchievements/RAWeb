import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildNumVisibleLeaderboardsColumnDefProps {
  t_label: TranslatedString;

  tableApiRouteName?: RouteName;
}

export function buildNumVisibleLeaderboardsColumnDef({
  t_label,
  tableApiRouteName = 'api.game.index',
}: BuildNumVisibleLeaderboardsColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'numVisibleLeaderboards',
    accessorKey: 'game',
    meta: {
      t_label,
      align: 'right',
      sortType: 'quantity',
      Icon: gameListFieldIconMap.numVisibleLeaderboards,
    },

    header: ({ column, table }) => (
      <DataTableColumnHeader column={column} table={table} tableApiRouteName={tableApiRouteName} />
    ),

    cell: ({ row }) => {
      // eslint-disable-next-line react-hooks/rules-of-hooks -- the cell component is a FC. using this hook doesn't break the rules of hooks.
      const { formatNumber } = useFormatNumber();

      const numVisibleLeaderboards = row.original.game?.numVisibleLeaderboards ?? 0;

      return (
        <p className={numVisibleLeaderboards === 0 ? 'text-muted' : ''}>
          {formatNumber(numVisibleLeaderboards)}
        </p>
      );
    },
  };
}
