import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildRetroRatioColumnDefProps {
  t_label: TranslatedString;
  strings: { t_none: TranslatedString };

  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildRetroRatioColumnDef({
  strings,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildRetroRatioColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'retroRatio',
    accessorKey: 'game',
    meta: { t_label, align: 'right', sortType: 'quantity', Icon: gameListFieldIconMap.retroRatio },

    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      const pointsTotal = row.original.game?.pointsTotal ?? 0;

      if (pointsTotal === 0) {
        return <p className="text-muted italic">{strings.t_none}</p>;
      }

      const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

      return <p>{buildGameRarityLabel(pointsTotal, pointsWeighted)}</p>;
    },
  };
}
