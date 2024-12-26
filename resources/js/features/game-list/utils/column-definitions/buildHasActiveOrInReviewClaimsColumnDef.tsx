import type { ColumnDef } from '@tanstack/react-table';
import { LuCheck, LuX } from 'react-icons/lu';
import type { RouteName } from 'ziggy-js';

import { UserAvatarStack } from '@/common/components/UserAvatarStack';
import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildHasActiveOrInReviewClaimsColumnDefProps {
  strings: {
    t_no: TranslatedString;
    t_yes: TranslatedString;
  };
  t_label: TranslatedString;

  tableApiRouteName?: RouteName;
  tableApiRouteParams?: Record<string, unknown>;
}

export function buildHasActiveOrInReviewClaimsColumnDef({
  strings,
  t_label,
  tableApiRouteParams,
  tableApiRouteName = 'api.game.index',
}: BuildHasActiveOrInReviewClaimsColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'hasActiveOrInReviewClaims',
    accessorKey: 'game',
    meta: { t_label, sortType: 'boolean', Icon: gameListFieldIconMap.hasActiveOrInReviewClaims },

    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        tableApiRouteName={tableApiRouteName}
        tableApiRouteParams={tableApiRouteParams}
      />
    ),

    cell: ({ row }) => {
      const hasActiveOrInReviewClaims = row.original.game?.hasActiveOrInReviewClaims ?? false;

      return (
        <div>
          {hasActiveOrInReviewClaims && row.original.game.claimants ? (
            <div className="flex items-center gap-1.5">
              <LuCheck className="size-4" />
              <span className="sr-only">{strings.t_yes}</span>

              <UserAvatarStack
                users={row.original.game.claimants.map((c) => c.user)}
                maxVisible={3}
                size={28}
              />
            </div>
          ) : (
            <>
              <LuX className="text-muted size-4" />
              <span className="sr-only">{strings.t_no}</span>
            </>
          )}
        </div>
      );
    },
  };
}
