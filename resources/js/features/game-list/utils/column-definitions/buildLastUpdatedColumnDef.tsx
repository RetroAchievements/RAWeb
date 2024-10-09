import type { ColumnDef } from '@tanstack/react-table';
import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';
import type { RouteName } from 'ziggy-js';

import { formatDate } from '@/common/utils/l10n/formatDate';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

dayjs.extend(utc);
dayjs.extend(localizedFormat);

interface BuildLastUpdatedColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildLastUpdatedColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildLastUpdatedColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'lastUpdated',
    accessorKey: 'game',
    meta: { label: 'Last Updated' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="date"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const date = row.original.game?.lastUpdated ?? new Date();

      return <p>{formatDate(dayjs.utc(date), 'll')}</p>;
    },
  };
}
