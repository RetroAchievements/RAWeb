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
              ? 'min-w-[180px] max-w-[286px] xl:min-w-[286px]'
              : 'min-w-[180px] max-w-[400px] xl:min-w-[320px]',
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
