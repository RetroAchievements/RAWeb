import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { GameAvatar } from '@/common/components/GameAvatar';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildTitleColumnDefProps<TEntry> {
  t_label: TranslatedString;

  forUsername?: string;
  options?: Partial<ColumnDef<TEntry>>;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildTitleColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  forUsername,
  options,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildTitleColumnDefProps<TEntry>): ColumnDef<TEntry> {
  return {
    id: 'title',
    accessorKey: 'game',
    meta: { t_label, Icon: gameListFieldIconMap.title },
    enableHiding: false,
    enableSorting: true,

    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      return (
        <div className="min-w-[180px] xl:min-w-[370px]">
          <div className="max-w-[400px]">
            <GameAvatar
              {...row.original.game}
              size={32}
              showHoverCardProgressForUsername={forUsername}
            />
          </div>
        </div>
      );
    },

    ...options,
  };
}
