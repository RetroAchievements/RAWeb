import { useTranslation } from 'react-i18next';
import type { NameType, Payload, ValueType } from 'recharts/types/component/DefaultTooltipContent';

import type { BaseChartConfig } from '@/common/components/+vendor/BaseChart';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';

import { getUserBucketIndexes } from './getUserBucketIndexes';

interface UseAchievementDistributionChartProps {
  buckets: App.Platform.Data.PlayerAchievementChartBucket[];
  playerGame: App.Platform.Data.PlayerGame | null;
  variant: 'game' | 'event';
}

export function useAchievementDistributionChart({
  buckets,
  playerGame,
  variant,
}: UseAchievementDistributionChartProps) {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  let hardcoreLabel = t('Hardcore Players');
  if (variant === 'event') {
    hardcoreLabel = t('Players');
  }

  const chartConfig = {
    softcore: {
      label: t('Softcore Players'),
      color: '#737373',
    },
    hardcore: {
      label: hardcoreLabel,
      color: '#cc9900',
    },
  } satisfies BaseChartConfig;

  const { userHardcoreIndex, userSoftcoreIndex } = getUserBucketIndexes(buckets, playerGame);

  // Get the player achievement counts for tooltips.
  const userAchievementCounts = playerGame
    ? {
        softcore: playerGame.achievementsUnlocked,
        hardcore: playerGame.achievementsUnlockedHardcore,
      }
    : null;

  const formatTooltipLabel = (
    label: ValueType,
    payload: Payload<ValueType, NameType>[],
  ): string => {
    if (!payload || !payload.length || !payload[0].payload) {
      return '';
    }

    const data = payload[0].payload as App.Platform.Data.PlayerAchievementChartBucket;
    const { start, end } = data;

    if (start !== end) {
      return t('Earned {{start, number}}–{{end, number}} achievements', { start, end });
    }

    return t('distributionEarned', { count: start, val: start });
  };

  const formatXAxisTick = (index: number): string => {
    const dataPoint = buckets[index];

    return formatNumber(dataPoint.start);
  };

  return {
    chartConfig,
    formatTooltipLabel,
    formatXAxisTick,
    userAchievementCounts,
    userHardcoreIndex,
    userSoftcoreIndex,
  };
}
