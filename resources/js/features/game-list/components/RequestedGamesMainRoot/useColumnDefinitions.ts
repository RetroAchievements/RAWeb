import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { buildAchievementsPublishedColumnDef } from '../../utils/column-definitions/buildAchievementsPublishedColumnDef';
import { buildHasActiveOrInReviewClaimsColumnDef } from '../../utils/column-definitions/buildHasActiveOrInReviewClaimsColumnDef';
import { buildNumRequestsColumnDef } from '../../utils/column-definitions/buildNumRequestsColumnDef';
import { buildReleasedAtColumnDef } from '../../utils/column-definitions/buildReleasedAtColumnDef';
import { buildRowActionsColumnDef } from '../../utils/column-definitions/buildRowActionsColumnDef';
import { buildSystemColumnDef } from '../../utils/column-definitions/buildSystemColumnDef';
import { buildTitleColumnDef } from '../../utils/column-definitions/buildTitleColumnDef';

export function useColumnDefinitions(
  targetUser?: App.Data.User | null,
): ColumnDef<App.Platform.Data.GameListEntry>[] {
  const { t } = useTranslation();

  const columnDefinitions = useMemo(() => {
    const columns: ColumnDef<App.Platform.Data.GameListEntry>[] = [
      buildTitleColumnDef({ t_label: t('Title') }),
      buildSystemColumnDef({ t_label: t('System') }),
      buildReleasedAtColumnDef({
        t_label: t('Release Date'),
        strings: { t_unknown: t('unknown') },
      }),
      buildNumRequestsColumnDef({ t_label: t('Requests') }),
    ];

    // Only show the achievements column for user-specific views.
    if (targetUser) {
      columns.push(buildAchievementsPublishedColumnDef({ t_label: t('Achievements') }));
    }

    columns.push(
      buildHasActiveOrInReviewClaimsColumnDef({
        t_label: t('Claimed'),
        strings: {
          t_no: t('No'),
          t_yes: t('Yes'),
        },
      }),
      buildRowActionsColumnDef({ shouldAnimateBacklogIconOnChange: true }),
    );

    return columns;
  }, [t, targetUser]);

  return columnDefinitions;
}
