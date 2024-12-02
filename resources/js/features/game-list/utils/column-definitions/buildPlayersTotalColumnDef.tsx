import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildPlayersTotalColumnDefProps {
  t_label: TranslatedString;

  tableApiRouteName?: RouteName;
}

export function buildPlayersTotalColumnDef({
  t_label,
  tableApiRouteName = 'api.game.index',
}: BuildPlayersTotalColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'playersTotal',
    accessorKey: 'game',
    meta: {
      t_label,
      align: 'right',
      sortType: 'quantity',
      Icon: gameListFieldIconMap.playersTotal,
    },

    header: ({ column, table }) => (
      <DataTableColumnHeader column={column} table={table} tableApiRouteName={tableApiRouteName} />
    ),

    cell: ({ row }) => {
      // eslint-disable-next-line react-hooks/rules-of-hooks -- the cell component is a FC. using this hook doesn't break the rules of hooks.
      const { formatNumber } = useFormatNumber();

      const playersTotal = row.original.game?.playersTotal ?? 0;

      return <p className={playersTotal === 0 ? 'text-muted' : ''}>{formatNumber(playersTotal)}</p>;
    },
  };
}
