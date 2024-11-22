import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildAchievementsPublishedColumnDefProps {
  t_label: string;

  tableApiRouteName?: RouteName;
}

export function buildAchievementsPublishedColumnDef({
  t_label,
  tableApiRouteName = 'api.game.index',
}: BuildAchievementsPublishedColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'achievementsPublished',
    accessorKey: 'game',
    meta: {
      t_label,
      align: 'right',
      sortType: 'quantity',
      Icon: gameListFieldIconMap.achievementsPublished,
    },

    header: ({ column, table }) => (
      <DataTableColumnHeader column={column} table={table} tableApiRouteName={tableApiRouteName} />
    ),

    cell: ({ row }) => {
      const achievementsPublished = row.original.game?.achievementsPublished ?? 0;

      return (
        <p className={achievementsPublished === 0 ? 'text-muted' : ''}>{achievementsPublished}</p>
      );
    },
  };
}
