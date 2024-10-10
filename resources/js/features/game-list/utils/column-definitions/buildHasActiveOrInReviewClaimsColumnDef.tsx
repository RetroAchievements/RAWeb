import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildHasActiveOrInReviewClaimsColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildHasActiveOrInReviewClaimsColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildHasActiveOrInReviewClaimsColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'hasActiveOrInReviewClaims',
    accessorKey: 'game',
    meta: { label: 'Claimed', align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        sortType="boolean"
      />
    ),
    cell: ({ row }) => {
      const hasActiveOrInReviewClaims = row.original.game?.hasActiveOrInReviewClaims ?? false;

      return (
        <div className="flex justify-end">
          {hasActiveOrInReviewClaims ? (
            <BaseTooltip>
              <BaseTooltipTrigger asChild>
                <p>Yes</p>
              </BaseTooltipTrigger>

              <BaseTooltipContent>
                <p className="text-xs">
                  One or more developers are currently working on this game.
                </p>
              </BaseTooltipContent>
            </BaseTooltip>
          ) : (
            <p className="text-muted">-</p>
          )}
        </div>
      );
    },
  };
}
