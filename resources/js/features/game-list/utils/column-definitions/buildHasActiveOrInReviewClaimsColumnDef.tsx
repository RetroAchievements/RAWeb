import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildHasActiveOrInReviewClaimsColumnDefProps {
  t_label: TranslatedString;
  strings: {
    t_yes: TranslatedString;
    t_description: TranslatedString;
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
    meta: { t_label, sortType: 'boolean', Icon: gameListFieldIconMap.hasActiveOrInReviewClaims },

    header: ({ column, table }) => (
      <DataTableColumnHeader column={column} table={table} tableApiRouteName={tableApiRouteName} />
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
