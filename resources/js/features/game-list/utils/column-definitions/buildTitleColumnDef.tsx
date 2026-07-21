import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { GameAvatar } from '@/common/components/GameAvatar';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildTitleColumnDefProps<TEntry> {
  t_label: TranslatedString;

  forUsername?: string;
  options?: Partial<ColumnDef<TEntry>> & { isSpaceConstrained?: boolean };
  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildTitleColumnDef<TEntry extends App.Platform.Data.GameListEntry>({
  forUsername,
  t_label,
  tableApiRouteParams,
  options = {},
  tableApiRouteName = 'api.game.index',
}: BuildTitleColumnDefProps<TEntry>): ColumnDef<TEntry> {
  const { isSpaceConstrained, ...restOptions } = options;

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
        <div
          className={cn(
            isSpaceConstrained
              ? 'min-w-45 max-w-71.5 xl:min-w-71.5'
              : 'min-w-45 max-w-100 xl:min-w-80',
          )}
        >
          <GameAvatar
            {...row.original.game}
            size={32}
            showHoverCardProgressForUsername={forUsername}
          />
        </div>
      );
    },

    ...restOptions,
  };
}
