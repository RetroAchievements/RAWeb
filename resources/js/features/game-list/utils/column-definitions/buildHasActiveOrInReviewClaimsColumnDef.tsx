import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildHasActiveOrInReviewClaimsColumnDefProps {
  t_label: string;
  strings: {
    t_yes: string;
    t_description: string;
  };

  tableApiRouteName?: RouteName;
}

export function buildHasActiveOrInReviewClaimsColumnDef({
  t_label,
  strings,
  tableApiRouteName = 'api.game.index',
}: BuildHasActiveOrInReviewClaimsColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'hasActiveOrInReviewClaims',
    accessorKey: 'game',
    meta: { t_label },
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
        <div>
          {hasActiveOrInReviewClaims ? (
            <BaseTooltip>
              <BaseTooltipTrigger asChild>
                <p>{strings.t_yes}</p>
              </BaseTooltipTrigger>

              <BaseTooltipContent>
                <p className="text-xs">{strings.t_description}</p>
              </BaseTooltipContent>
            </BaseTooltip>
          ) : (
            <p className="text-muted">{'-'}</p>
          )}
        </div>
      );
    },
  };
}
