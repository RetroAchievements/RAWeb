import type { FC } from 'react';

import { cn } from '@/common/utils/cn';
import { formatPercentage } from '@/common/utils/l10n/formatPercentage';

interface ProgressBarProps {
  totalAchievementsCount: number;
  numEarnedAchievements: number;
}

export const ProgressBar: FC<ProgressBarProps> = ({
  numEarnedAchievements,
  totalAchievementsCount,
}) => {
  let progressWidth = 0;
  let completionPercentage = 0;

  if (totalAchievementsCount > 0) {
    progressWidth = (numEarnedAchievements / totalAchievementsCount) * 100;
    completionPercentage = Math.floor(progressWidth);
  }

  return (
    <div
      role="progressbar"
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={completionPercentage}
      className="absolute bottom-0 left-0 flex h-2 w-full bg-embed-highlight lg:rounded-b"
    >
      {completionPercentage > 0 && completionPercentage < 99 ? (
        <p
          className="absolute bottom-2 select-none text-[0.65rem] text-yellow-500 opacity-0 group-hover:opacity-100 light:text-yellow-700"
          style={{ left: `calc(${completionPercentage}% - 10px)` }}
        >
          {formatPercentage(completionPercentage / 100, {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0,
          })}
        </p>
      ) : null}

      <div
        className={cn(
          'h-full bg-yellow-500 lg:rounded-bl',
          progressWidth === 100 ? 'lg:rounded-br' : null,
        )}
        style={{ width: `${progressWidth}%` }}
      />
    </div>
  );
};
