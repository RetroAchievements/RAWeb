import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { SystemChip } from '@/common/components/SystemChip';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildSystemColumnDefProps {
  t_label: TranslatedString;

  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildSystemColumnDef({
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildSystemColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'system',
    accessorKey: 'game',
    meta: { t_label, Icon: gameListFieldIconMap.system },

    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      if (!row.original.game?.system) {
        return null;
      }

      return <SystemChip {...row.original.game.system} />;
    },
  };
}
