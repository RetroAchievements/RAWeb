import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';
import type { RouteName } from 'ziggy-js';

import { doesColumnExist } from '@/features/game-list/utils/doesColumnExist';

type AchievementsPublishedFilterValue = 'has' | 'none' | 'either';

export function useCurrentSuperFilterLabel<TData>(
  table: Table<TData>,
  tableApiRouteName?: RouteName,
): string {
  const { t } = useTranslation();

  const allColumns = table.getAllColumns();

  const isAchievementsPublishedColumnAvailable = doesColumnExist(
    allColumns,
    'achievementsPublished',
  );
  const achievementsPublished = isAchievementsPublishedColumnAvailable
    ? table.getColumn('achievementsPublished')
    : undefined;
  const achievementsPublishedFilterValue =
    achievementsPublished?.getFilterValue() as AchievementsPublishedFilterValue;

  const filterLabelMap: Record<string, string> = {
    has: t('Playable'),
    none: t('Not Playable'),
    default: t('All Games'),
  };

  let filterLabel = filterLabelMap[achievementsPublishedFilterValue] || filterLabelMap.default;

  const isSystemColumnAvailable = doesColumnExist(allColumns, 'system');
  if (isSystemColumnAvailable) {
    const system = table.getColumn('system');
    const systemFilterValue = system?.getFilterValue() as string[] | undefined;

    const systemsCount = systemFilterValue?.length ?? 0;
    if (systemsCount > 0) {
      // Check if we're on the Most Requested Sets page and "supported" is selected.
      if (
        tableApiRouteName?.includes('api.set-request') &&
        systemFilterValue?.length === 1 &&
        systemFilterValue[0] === 'supported'
      ) {
        filterLabel += `, ${t('Supported')}`;
      } else {
        const systemsLabel = t('{{val, number}} Systems', {
          count: systemsCount,
          val: systemsCount,
        });
        filterLabel += `, ${systemsLabel}`;
      }
    } else {
      filterLabel += `, ${t('All Systems')}`;
    }
  }

  return filterLabel;
}
