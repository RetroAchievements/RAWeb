import type { ColumnDef } from '@tanstack/react-table';

import { buildAchievementsPublishedColumnDef } from '../../utils/column-definitions/buildAchievementsPublishedColumnDef';
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

export function buildColumnDefinitions(options: {
  canSeeOpenTicketsColumn: boolean;
  forUsername?: string;
}): ColumnDef<App.Platform.Data.GameListEntry>[] {
  const columnDefinitions: ColumnDef<App.Platform.Data.GameListEntry>[] = [
    buildTitleColumnDef({ forUsername: options.forUsername }),
    buildSystemColumnDef({}),
    buildAchievementsPublishedColumnDef({}),
    buildPointsTotalColumnDef({}),
    buildRetroRatioColumnDef({}),
    buildLastUpdatedColumnDef({}),
    buildReleasedAtColumnDef({}),
    buildPlayersTotalColumnDef({}),
    buildNumVisibleLeaderboardsColumnDef({}),
  ];

  if (options.canSeeOpenTicketsColumn) {
    columnDefinitions.push(buildNumUnresolvedTicketsColumnDef({}));
  }

  columnDefinitions.push(
    ...([
      buildPlayerGameProgressColumnDef({}),
      buildRowActionsColumnDef(),
    ] satisfies ColumnDef<App.Platform.Data.GameListEntry>[]),
  );

  return columnDefinitions;
}
