import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import type { RouteName } from 'ziggy-js';

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
import { buildSystemColumnDef } from '../../utils/column-definitions/buildSystemColumnDef';
import { buildTitleColumnDef } from '../../utils/column-definitions/buildTitleColumnDef';

const tableApiRouteName: RouteName = 'api.user-game-list.index';

export function useColumnDefinitions(options: {
  canSeeOpenTicketsColumn: boolean;
  forUsername?: string;
}): ColumnDef<App.Platform.Data.GameListEntry>[] {
  const { t } = useTranslation();

  const columnDefinitions = useMemo(() => {
    const columns: ColumnDef<App.Platform.Data.GameListEntry>[] = [
      buildTitleColumnDef({
        tableApiRouteName,
        t_label: t('Title'),
        forUsername: options.forUsername,
      }),
      buildSystemColumnDef({ tableApiRouteName, t_label: t('System') }),
      buildAchievementsPublishedColumnDef({ tableApiRouteName, t_label: t('Achievements') }),
      buildPointsTotalColumnDef({ tableApiRouteName, t_label: t('Points') }),
      buildRetroRatioColumnDef({
        tableApiRouteName,
        t_label: t('Rarity'),
        strings: { t_none: t('none') },
      }),
      buildLastUpdatedColumnDef({ tableApiRouteName, t_label: t('Last Updated') }),
      buildReleasedAtColumnDef({
        tableApiRouteName,
        t_label: t('Release Date'),
        strings: { t_unknown: t('unknown') },
      }),
      buildPlayersTotalColumnDef({ tableApiRouteName, t_label: t('Players') }),
      buildNumVisibleLeaderboardsColumnDef({ tableApiRouteName, t_label: t('Leaderboards') }),
    ];

    if (options.canSeeOpenTicketsColumn) {
      columns.push(
        buildNumUnresolvedTicketsColumnDef({ tableApiRouteName, t_label: t('Tickets') }),
      );
    }

    columns.push(
      ...([
        buildPlayerGameProgressColumnDef({ tableApiRouteName, t_label: t('Progress') }),
        buildHasActiveOrInReviewClaimsColumnDef({
          tableApiRouteName,
          t_label: t('Claimed'),
          strings: {
            t_description: t('One or more developers are currently working on this game.'),
            t_yes: t('Yes'),
          },
        }),
        buildRowActionsColumnDef({ shouldAnimateBacklogIconOnChange: false }),
      ] satisfies ColumnDef<App.Platform.Data.GameListEntry>[]),
    );

    return columns;
  }, [options.canSeeOpenTicketsColumn, options.forUsername, t]);

  return columnDefinitions;
}
