import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { buildAchievementsPublishedColumnDef } from '../../utils/column-definitions/buildAchievementsPublishedColumnDef';
import { buildPlayerGameProgressColumnDef } from '../../utils/column-definitions/buildPlayerGameProgressColumnDef';
import { buildPlayersTotalColumnDef } from '../../utils/column-definitions/buildPlayersTotalColumnDef';
import { buildPointsTotalColumnDef } from '../../utils/column-definitions/buildPointsTotalColumnDef';
import { buildRowActionsColumnDef } from '../../utils/column-definitions/buildRowActionsColumnDef';
import { buildSuggestionReasonColumnDef } from '../../utils/column-definitions/buildSuggestionReasonColumnDef';
import { buildSystemColumnDef } from '../../utils/column-definitions/buildSystemColumnDef';
import { buildTitleColumnDef } from '../../utils/column-definitions/buildTitleColumnDef';

export function useColumnDefinitions(options: {
  forUsername: string;
}): ColumnDef<App.Platform.Data.GameSuggestionEntry>[] {
  const { t } = useTranslation();

  const columnDefinitions = useMemo(() => {
    const columns: ColumnDef<App.Platform.Data.GameSuggestionEntry>[] = [
      buildTitleColumnDef({
        t_label: t('Title'),
        forUsername: options.forUsername,
        options: { enableSorting: false, isSpaceConstrained: true },
      }),
      buildSystemColumnDef({ t_label: t('System'), options: { enableSorting: false } }),
      buildAchievementsPublishedColumnDef({
        t_label: t('Achievements'),
        options: { enableSorting: false },
      }),
      buildPointsTotalColumnDef({ t_label: t('Points'), options: { enableSorting: false } }),
      buildPlayersTotalColumnDef({ t_label: t('Players'), options: { enableSorting: false } }),
      buildSuggestionReasonColumnDef({ t_label: t('Reasoning') }),
      buildPlayerGameProgressColumnDef({
        t_label: t('Progress'),
        options: { enableSorting: false },
      }),
      buildRowActionsColumnDef({ shouldAnimateBacklogIconOnChange: true }),
    ];

    return columns;
  }, [options.forUsername, t]);

  return columnDefinitions;
}
