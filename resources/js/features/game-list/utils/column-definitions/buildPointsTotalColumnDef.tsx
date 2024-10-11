import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildPointsTotalColumnDefProps {
  t_label: string;

  tableApiRouteName?: RouteName;
}

export function buildPointsTotalColumnDef({
  t_label,
  tableApiRouteName = 'api.game.index',
}: BuildPointsTotalColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'pointsTotal',
    accessorKey: 'game',
    meta: { t_label, align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      // eslint-disable-next-line react-hooks/rules-of-hooks -- the cell component is a FC. using this hook doesn't break the rules of hooks.
      const { formatNumber } = useFormatNumber();

      const pointsTotal = row.original.game?.pointsTotal ?? 0;
      const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

      if (pointsTotal === 0) {
        return <p className="text-muted">{pointsTotal}</p>;
      }

      return (
        <div className="whitespace-nowrap">
          {formatNumber(pointsTotal)}{' '}
          {/* eslint-disable-next-line react/jsx-no-literals -- this is valid */}
          <WeightedPointsContainer>({formatNumber(pointsWeighted)})</WeightedPointsContainer>
        </div>
      );
    },
  };
}
