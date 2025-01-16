import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildAchievementsPublishedColumnDefProps<TEntry> {
  t_label: TranslatedString;

  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
  options?: Partial<ColumnDef<TEntry>>;
}

export function buildAchievementsPublishedColumnDef<
  TEntry extends App.Platform.Data.GameListEntry,
>({
  options,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildAchievementsPublishedColumnDefProps<TEntry>): ColumnDef<TEntry> {
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
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      const achievementsPublished = row.original.game?.achievementsPublished ?? 0;

      return (
        <p className={achievementsPublished === 0 ? 'text-muted' : ''}>{achievementsPublished}</p>
      );
    },

    ...options,
  };
}
