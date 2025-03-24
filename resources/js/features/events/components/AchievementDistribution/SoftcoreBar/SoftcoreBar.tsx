import type { FC } from 'react';
import { Bar } from 'recharts';

/**
 * Extracted from AchievementDistribution just so we can test the logic.
 * AchievementDistribution cannot render children in JSDOM, so we have to
 * test this component of the chart in isolation.
 */

interface SoftcoreBarProps {
  variant: 'event' | 'game';
}

export const SoftcoreBar: FC<SoftcoreBarProps> = ({ variant }) => {
  if (variant === 'event') {
    return null;
  }

  return (
    <Bar
      data-testid="softcore-bar"
      dataKey="softcore"
      fill="var(--color-softcore)"
      stackId="a"
      isAnimationActive={false}
    />
  );
};
