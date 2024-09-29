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

      <BaseTooltipContent>
        <div className="flex flex-col items-center text-center text-xs">
          <p>RetroPoints: A measurement of rarity and estimated difficulty.</p>
          <p>Derived from points, number of achievers, and number of players.</p>
        </div>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
