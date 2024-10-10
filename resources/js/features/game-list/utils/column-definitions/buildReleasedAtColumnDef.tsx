import type { ColumnDef } from '@tanstack/react-table';
import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';
import type { RouteName } from 'ziggy-js';

import { formatDate } from '@/common/utils/l10n/formatDate';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

dayjs.extend(utc);
dayjs.extend(localizedFormat);

interface BuildReleasedAtColumnDefProps {
  t_label: string;
  strings: { t_unknown: string };

  tableApiRouteName?: RouteName;
}

export function buildReleasedAtColumnDef({
  t_label,
  strings,
  tableApiRouteName = 'api.game.index',
}: BuildReleasedAtColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'releasedAt',
    accessorKey: 'game',
    meta: { t_label },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="date"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const date = row.original.game?.releasedAt ?? null;
      const granularity = row.original.game?.releasedAtGranularity ?? 'day';

      if (!date) {
        return <p className="text-muted italic">{strings.t_unknown}</p>;
      }

      const dayjsDate = dayjs.utc(date);
      let formattedDate;
      if (granularity === 'day') {
        formattedDate = formatDate(dayjsDate, 'll');
      } else if (granularity === 'month') {
        formattedDate = dayjsDate.format('MMM YYYY');
      } else {
        formattedDate = dayjsDate.format('YYYY');
      }

      return <p>{formattedDate}</p>;
    },
  };
}
