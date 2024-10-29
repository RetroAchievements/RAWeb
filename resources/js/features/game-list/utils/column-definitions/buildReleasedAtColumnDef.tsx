import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

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
    meta: { t_label, sortType: 'date', Icon: gameListFieldIconMap.releasedAt },
    header: ({ column, table }) => (
      <DataTableColumnHeader column={column} table={table} tableApiRouteName={tableApiRouteName} />
    ),
    cell: ({ row }) => {
      const date = row.original.game?.releasedAt ?? null;
      const granularity = row.original.game?.releasedAtGranularity ?? 'day';

      if (!date) {
        return <p className="text-muted italic">{strings.t_unknown}</p>;
      }

      return <p>{formatGameReleasedAt(date, granularity)}</p>;
    },
  };
}
