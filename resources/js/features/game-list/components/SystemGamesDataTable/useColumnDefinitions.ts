import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { RouteName } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';

import { buildAchievementsPublishedColumnDef } from '../../utils/column-definitions/buildAchievementsPublishedColumnDef';
import { buildHasActiveOrInReviewClaimsColumnDef } from '../../utils/column-definitions/buildHasActiveOrInReviewClaimsColumnDef';
import { buildLastUpdatedColumnDef } from '../../utils/column-definitions/buildLastUpdatedColumnDef';
import { buildNumUnresolvedTicketsColumnDef } from '../../utils/column-definitions/buildNumUnresolvedTicketsColumnDef';
import { buildNumVisibleLeaderboardsColumnDef } from '../../utils/column-definitions/buildNumVisibleLeaderboardsColumnDef';
import { buildPlayerGameProgressColumnDef } from '../../utils/column-definitions/buildPlayerGameProgressColumnDef';
import { buildPlayersTotalColumnDef } from '../../utils/column-definitions/buildPlayersTotalColumnDef';
import { buildPointsTotalColumnDef } from '../../utils/column-definitions/buildPointsTotalColumnDef';
import { buildReleasedAtColumnDef } from '../../utils/column-definitions/buildReleasedAtColumnDef';
import { buildRetroRatioColumnDef } from '../../utils/column-definitions/buildRetroRatioColumnDef';
import { buildRowActionsColumnDef } from '../../utils/column-definitions/buildRowActionsColumnDef';
import { buildTitleColumnDef } from '../../utils/column-definitions/buildTitleColumnDef';

const tableApiRouteName: RouteName = 'api.system.game.index';

export function useColumnDefinitions(options: {
  canSeeOpenTicketsColumn: boolean;
  forUsername?: string;
}): ColumnDef<App.Platform.Data.GameListEntry>[] {
  const { system } = usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const { t } = useTranslation();

  const columnDefinitions = useMemo(() => {
    const tableApiRouteParams = { systemId: system.id };

    const columns: ColumnDef<App.Platform.Data.GameListEntry>[] = [
      buildTitleColumnDef({
        tableApiRouteName,
        tableApiRouteParams,
        t_label: t('Title'),
        forUsername: options.forUsername,
      }),
      buildAchievementsPublishedColumnDef({
        tableApiRouteName,
        tableApiRouteParams,
        t_label: t('Achievements'),
      }),
      buildPointsTotalColumnDef({ tableApiRouteName, tableApiRouteParams, t_label: t('Points') }),
      buildRetroRatioColumnDef({
        tableApiRouteName,
        tableApiRouteParams,
        t_label: t('Rarity'),
        strings: { t_none: t('none') },
      }),
      buildLastUpdatedColumnDef({
        tableApiRouteName,
        tableApiRouteParams,
        t_label: t('Last Updated'),
      }),
      buildReleasedAtColumnDef({
        tableApiRouteName,
        tableApiRouteParams,
        t_label: t('Release Date'),
        strings: { t_unknown: t('unknown') },
      }),
      buildPlayersTotalColumnDef({ tableApiRouteName, tableApiRouteParams, t_label: t('Players') }),
      buildNumVisibleLeaderboardsColumnDef({
        tableApiRouteName,
        tableApiRouteParams,
        t_label: t('Leaderboards'),
      }),
    ];

    if (options.canSeeOpenTicketsColumn) {
      columns.push(
        buildNumUnresolvedTicketsColumnDef({
          tableApiRouteName,
          tableApiRouteParams,
          t_label: t('Tickets'),
        }),
      );
    }

    columns.push(
      ...([
        buildPlayerGameProgressColumnDef({
          tableApiRouteName,
          tableApiRouteParams,
          t_label: t('Progress'),
        }),
        buildHasActiveOrInReviewClaimsColumnDef({
          tableApiRouteName,
          tableApiRouteParams,
          t_label: t('Claimed'),
          strings: {
            t_no: t('No'),
            t_yes: t('Yes'),
          },
        }),
        buildRowActionsColumnDef({ shouldAnimateBacklogIconOnChange: true }),
      ] satisfies ColumnDef<App.Platform.Data.GameListEntry>[]),
    );

    return columns;
  }, [options.canSeeOpenTicketsColumn, options.forUsername, system.id, t]);

  return columnDefinitions;
}
