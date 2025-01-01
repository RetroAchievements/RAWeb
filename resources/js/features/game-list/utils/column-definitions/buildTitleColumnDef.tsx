import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { GameAvatar } from '@/common/components/GameAvatar';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildTitleColumnDefProps {
  t_label: TranslatedString;

  forUsername?: string;
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildTitleColumnDef({
  forUsername,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildTitleColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'title',
    accessorKey: 'game',
    meta: { t_label, Icon: gameListFieldIconMap.title },
    enableHiding: false,

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
        <div className="min-w-[180px]">
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
