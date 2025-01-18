import type { ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { buildAchievementsPublishedColumnDef } from '../../utils/column-definitions/buildAchievementsPublishedColumnDef';
import { buildPlayerGameProgressColumnDef } from '../../utils/column-definitions/buildPlayerGameProgressColumnDef';
import { buildPlayersTotalColumnDef } from '../../utils/column-definitions/buildPlayersTotalColumnDef';
import { buildPointsTotalColumnDef } from '../../utils/column-definitions/buildPointsTotalColumnDef';
import { buildRowActionsColumnDef } from '../../utils/column-definitions/buildRowActionsColumnDef';
import { buildSuggestionReasonColumnDef } from '../../utils/column-definitions/buildSuggestionReasonColumnDef';
import { buildSystemColumnDef } from '../../utils/column-definitions/buildSystemColumnDef';
import { buildTitleColumnDef } from '../../utils/column-definitions/buildTitleColumnDef';

export function useColumnDefinitions(
  options: {
    showSourceGame: boolean;
  } = { showSourceGame: true },
): ColumnDef<App.Platform.Data.GameSuggestionEntry>[] {
  const { sourceGame } = usePageProps<App.Platform.Data.GameSuggestPageProps>();

  const { t } = useTranslation();

  const columnDefinitions = useMemo(() => {
    const columns: ColumnDef<App.Platform.Data.GameSuggestionEntry>[] = [
      buildTitleColumnDef({
        t_label: t('Title'),
        options: { enableSorting: false, isSpaceConstrained: true },
      }),
      buildSystemColumnDef({ t_label: t('System'), options: { enableSorting: false } }),
      buildAchievementsPublishedColumnDef({
        t_label: t('Achievements'),
        options: { enableSorting: false },
      }),
      buildPointsTotalColumnDef({ t_label: t('Points'), options: { enableSorting: false } }),
      buildPlayersTotalColumnDef({ t_label: t('Players'), options: { enableSorting: false } }),
      buildSuggestionReasonColumnDef({
        sourceGame: options.showSourceGame ? sourceGame : null,
        t_label: t('Reasoning'),
      }),
      buildPlayerGameProgressColumnDef({
        t_label: t('Progress'),
        options: { enableSorting: false },
      }),
      buildRowActionsColumnDef({ shouldAnimateBacklogIconOnChange: true }),
    ];

    return columns;
  }, [options.showSourceGame, sourceGame, t]);

  return columnDefinitions;
}
