import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildPointsTotalColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildPointsTotalColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildPointsTotalColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'pointsTotal',
    accessorKey: 'game',
    meta: { label: 'Points', align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const pointsTotal = row.original.game?.pointsTotal ?? 0;
      const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

      if (pointsTotal === 0) {
        return <p className="text-muted">{pointsTotal}</p>;
      }

      return (
        <p className="whitespace-nowrap">
          {formatNumber(pointsTotal)}{' '}
          <WeightedPointsContainer>({formatNumber(pointsWeighted)})</WeightedPointsContainer>
        </p>
      );
    },
  };
}
