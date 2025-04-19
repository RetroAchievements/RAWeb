import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

interface WeightedPointsContainerProps {
  children?: ReactNode;
  isTooltipEnabled?: boolean;
}

export const WeightedPointsContainer: FC<WeightedPointsContainerProps> = ({
  children,
  isTooltipEnabled = true,
}) => {
  const { t } = useTranslation();

  return (
    <BaseTooltip delayDuration={700} open={isTooltipEnabled ? undefined : false}>
      <BaseTooltipTrigger className="cursor-default" asChild>
        <span className="TrueRatio light:text-neutral-400">{children}</span>
      </BaseTooltipTrigger>

      <BaseTooltipContent asChild>
        <span className="flex flex-col items-center text-center text-xs">
          <span>{t('RetroPoints: A measurement of rarity and estimated difficulty.')}</span>
          <span>{t('Derived from points, number of achievers, and number of players.')}</span>
        </span>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
