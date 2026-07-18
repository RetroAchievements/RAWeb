import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { Bar, BarChart, CartesianGrid, ReferenceLine, XAxis } from 'recharts';

import { BaseChartContainer, BaseChartTooltip } from '@/common/components/+vendor/BaseChart';

import { ReferenceLineTooltipContent } from './ReferenceLineTooltipContent';
import { useAchievementDistributionChart } from './useAchievementDistributionChart';

interface PlayableAchievementDistributionProps {
  buckets: App.Platform.Data.PlayerAchievementChartBucket[];
  playerGame: App.Platform.Data.PlayerGame | null;
  variant: 'event' | 'game';
}

export const PlayableAchievementDistribution: FC<PlayableAchievementDistributionProps> = ({
  buckets,
  playerGame,
  variant,
}) => {
  const { t } = useTranslation();

  const {
    chartConfig,
    formatTooltipLabel,
    formatXAxisTick,
    userAchievementCounts,
    userHardcoreIndex,
    userCasualIndex,
  } = useAchievementDistributionChart({ buckets, playerGame, variant });

  if (buckets.length < 2) {
    return null;
  }

  return (
    <div data-testid="achievement-distribution">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Achievement Distribution')}</h2>

      <div className="flex flex-col gap-3 rounded-lg bg-embed p-3 light:border light:border-neutral-200 light:bg-white">
        <BaseChartContainer config={chartConfig} className="h-65 w-full">
          <BarChart accessibilityLayer data={buckets}>
            <CartesianGrid vertical={false} />
            <XAxis tickMargin={8} angle={0} tickFormatter={formatXAxisTick} />

            <BaseChartTooltip
              content={
                <ReferenceLineTooltipContent
                  className="min-w-49"
                  buckets={buckets}
                  labelFormatter={formatTooltipLabel}
                  userAchievementCounts={userAchievementCounts}
                  userHardcoreIndex={userHardcoreIndex}
                  userCasualIndex={userCasualIndex}
                  variant={variant}
                />
              }
            />

            <Bar
              dataKey="hardcore"
              fill="var(--color-hardcore)"
              stackId="a"
              isAnimationActive={false}
            />

            <Bar
              // Events do not track casual progress.
              hide={variant === 'event'}
              data-testid="casual-bar"
              dataKey="casual"
              fill="var(--color-casual)"
              stackId="a"
              isAnimationActive={false}
            />

            {userHardcoreIndex !== undefined ? (
              <ReferenceLine
                x={userHardcoreIndex}
                stroke="#cc9900"
                strokeDasharray="3 3"
                strokeWidth={2}
              />
            ) : null}

            {userCasualIndex !== undefined ? (
              <ReferenceLine
                x={userCasualIndex}
                stroke="#737373"
                strokeDasharray="3 3"
                strokeWidth={2}
              />
            ) : null}
          </BarChart>
        </BaseChartContainer>
      </div>
    </div>
  );
};
