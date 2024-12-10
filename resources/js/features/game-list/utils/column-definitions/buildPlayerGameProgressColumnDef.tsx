import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildPlayerGameProgressColumnDefProps {
  t_label: TranslatedString;

  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildPlayerGameProgressColumnDef({
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildPlayerGameProgressColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'progress',
    accessorKey: 'game',
    meta: { t_label, align: 'left', sortType: 'quantity', Icon: gameListFieldIconMap.progress },

    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      const { game, playerGame } = row.original;

      return <PlayerGameProgressBar game={game} playerGame={playerGame} />;
    },
  };
}
