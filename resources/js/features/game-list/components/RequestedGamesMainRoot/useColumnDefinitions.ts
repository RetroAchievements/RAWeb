import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { RouteName } from 'ziggy-js';

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

  const tableApiRouteName: RouteName = targetUser
    ? 'api.set-request.user'
    : 'api.set-request.index';

  const columnDefinitions = useMemo(() => {
    const columns: ColumnDef<App.Platform.Data.GameListEntry>[] = [
      buildTitleColumnDef({ t_label: t('Title'), tableApiRouteName }),
      buildSystemColumnDef({ t_label: t('System'), tableApiRouteName }),
      buildReleasedAtColumnDef({
        tableApiRouteName,
        t_label: t('Release Date'),
        strings: { t_unknown: t('unknown') },
      }),
      buildNumRequestsColumnDef({
        tableApiRouteName,
        t_label: t('Requests'),
      }),
    ];

    // Only show the achievements column for user-specific views.
    if (targetUser) {
      columns.push(buildAchievementsPublishedColumnDef({ t_label: t('Achievements') }));
    }

    columns.push(
      buildHasActiveOrInReviewClaimsColumnDef({
        tableApiRouteName,
        t_label: t('Claimed'),
        strings: {
          t_no: t('No'),
          t_yes: t('Yes'),
        },
      }),
      buildRowActionsColumnDef({ shouldAnimateBacklogIconOnChange: true }),
    );

    return columns;
  }, [t, tableApiRouteName, targetUser]);

  return columnDefinitions;
}
