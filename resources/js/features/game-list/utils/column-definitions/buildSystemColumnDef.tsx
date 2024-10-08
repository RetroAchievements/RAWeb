import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { SystemChip } from '@/common/components/SystemChip';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildSystemColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildSystemColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildSystemColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'system',
    accessorKey: 'game',
    meta: { label: 'System' },
    header: ({ column, table }) => (
      <DataTableColumnHeader column={column} table={table} tableApiRouteName={tableApiRouteName} />
    ),
    cell: ({ row }) => {
      if (!row.original.game?.system) {
        return null;
      }

      return <SystemChip {...row.original.game.system} />;
    },
  };
}
