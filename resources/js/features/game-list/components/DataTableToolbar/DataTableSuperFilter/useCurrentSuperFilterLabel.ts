import type { Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';

type AchievementsPublishedFilterValue = 'has' | 'none' | 'either';

export function useCurrentSuperFilterLabel<TData>(table: Table<TData>): string {
  const { t, tChoice } = useLaravelReactI18n();

  const { formatNumber } = useFormatNumber();

  const achievementsPublished = table.getColumn('achievementsPublished');
  const achievementsPublishedFilterValue =
    achievementsPublished?.getFilterValue() as AchievementsPublishedFilterValue;

  const system = table.getColumn('system');
  const systemFilterValue = system?.getFilterValue() as string[] | undefined;

  const filterLabelMap: Record<string, string> = {
    has: t('Playable'),
    none: t('Not Playable'),
    default: t('All Games'),
  };

  let filterLabel = filterLabelMap[achievementsPublishedFilterValue] || filterLabelMap.default;

  const systemsCount = systemFilterValue?.length ?? 0;
  if (systemsCount > 0) {
    const systemsLabel = tChoice(':count system|:count Systems', systemsCount, {
      count: formatNumber(systemsCount),
    });

    filterLabel += `, ${systemsLabel}`;
  } else {
    filterLabel += `, ${t('All Systems')}`;
  }

  return filterLabel;
}
