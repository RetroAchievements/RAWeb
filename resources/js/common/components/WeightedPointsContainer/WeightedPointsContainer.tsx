import type { FC, ReactNode } from 'react';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

interface WeightedPointsContainerProps {
  children: ReactNode;
}

export const WeightedPointsContainer: FC<WeightedPointsContainerProps> = ({ children }) => {
  return (
    <BaseTooltip delayDuration={700}>
      <BaseTooltipTrigger className="cursor-default">
        <span className="TrueRatio light:text-neutral-400">{children}</span>
      </BaseTooltipTrigger>

      <BaseTooltipContent asChild>
        <span className="flex flex-col items-center text-center text-xs">
          <span>RetroPoints: A measurement of rarity and estimated difficulty.</span>
          <span>Derived from points, number of achievers, and number of players.</span>
        </span>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
