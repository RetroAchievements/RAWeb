import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { Bar, BarChart, CartesianGrid, ReferenceLine, XAxis } from 'recharts';

import { BaseChartContainer, BaseChartTooltip } from '@/common/components/+vendor/BaseChart';

import { ReferenceLineTooltipContent } from './ReferenceLineTooltipContent';
import { SoftcoreBar } from './SoftcoreBar';
import { useAchievementDistributionChart } from './useAchievementDistributionChart';

interface AchievementDistributionProps {
  buckets: App.Platform.Data.PlayerAchievementChartBucket[];
  playerGame: App.Platform.Data.PlayerGame | null;
  variant: 'event' | 'game';
}

export const AchievementDistribution: FC<AchievementDistributionProps> = ({
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
    userSoftcoreIndex,
  } = useAchievementDistributionChart({ buckets, playerGame, variant });

  if (buckets.length < 2) {
    return null;
  }

  return (
    <div data-testid="achievement-distribution">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Achievement Distribution')}</h2>

      <div className="flex flex-col gap-3 rounded-lg bg-embed p-3">
        <BaseChartContainer config={chartConfig} className="h-[260px] w-full">
          <BarChart accessibilityLayer data={buckets}>
            <CartesianGrid vertical={false} />
            <XAxis tickMargin={8} angle={0} tickFormatter={formatXAxisTick} />

            <BaseChartTooltip
              content={
                <ReferenceLineTooltipContent
                  className="min-w-[196px]"
                  buckets={buckets}
                  labelFormatter={formatTooltipLabel}
                  userAchievementCounts={userAchievementCounts}
                  userHardcoreIndex={userHardcoreIndex}
                  userSoftcoreIndex={userSoftcoreIndex}
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
            {/* Events do not track softcore progress. */}
            <SoftcoreBar variant={variant} />

            {userHardcoreIndex !== undefined ? (
              <ReferenceLine
                x={userHardcoreIndex}
                stroke="#cc9900"
                strokeDasharray="3 3"
                strokeWidth={2}
              />
            ) : null}

            {userSoftcoreIndex !== undefined ? (
              <ReferenceLine
                x={userSoftcoreIndex}
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
