import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

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

const tableApiRouteName: RouteName = 'api.user-game-list.index';

export function buildColumnDefinitions(options: {
  canSeeOpenTicketsColumn: boolean;
  forUsername?: string;
}): ColumnDef<App.Platform.Data.GameListEntry>[] {
  const columnDefinitions: ColumnDef<App.Platform.Data.GameListEntry>[] = [
    buildTitleColumnDef({ tableApiRouteName, forUsername: options.forUsername }),
    buildSystemColumnDef({ tableApiRouteName }),
    buildAchievementsPublishedColumnDef({ tableApiRouteName }),
    buildPointsTotalColumnDef({ tableApiRouteName }),
    buildRetroRatioColumnDef({ tableApiRouteName }),
    buildLastUpdatedColumnDef({ tableApiRouteName }),
    buildReleasedAtColumnDef({ tableApiRouteName }),
    buildPlayersTotalColumnDef({ tableApiRouteName }),
    buildNumVisibleLeaderboardsColumnDef({ tableApiRouteName }),
  ];

  if (options.canSeeOpenTicketsColumn) {
    columnDefinitions.push(buildNumUnresolvedTicketsColumnDef({ tableApiRouteName }));
  }

  columnDefinitions.push(
    ...([
      buildPlayerGameProgressColumnDef({ tableApiRouteName }),
      buildRowActionsColumnDef(),
    ] satisfies ColumnDef<App.Platform.Data.GameListEntry>[]),
  );

  return columnDefinitions;
}
