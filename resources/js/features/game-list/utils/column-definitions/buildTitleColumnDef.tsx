import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { GameAvatar } from '@/common/components/GameAvatar';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildTitleColumnDefProps {
  t_label: string;

  forUsername?: string;
  tableApiRouteName?: RouteName;
}

export function buildTitleColumnDef({
  t_label,
  forUsername,
  tableApiRouteName = 'api.game.index',
}: BuildTitleColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'title',
    accessorKey: 'game',
    meta: { t_label, Icon: gameListFieldIconMap.title },
    enableHiding: false,
    header: ({ column, table }) => (
      <DataTableColumnHeader column={column} table={table} tableApiRouteName={tableApiRouteName} />
    ),
    cell: ({ row }) => {
      if (!row.original.game) {
        return null;
      }

      return (
        <div className="min-w-[180px] max-w-fit">
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
  };
}
