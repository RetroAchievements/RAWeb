import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC, ReactNode } from 'react';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

interface WeightedPointsContainerProps {
  children: ReactNode;

  isTooltipEnabled?: boolean;
}

export const WeightedPointsContainer: FC<WeightedPointsContainerProps> = ({
  children,
  isTooltipEnabled = true,
}) => {
  const { t } = useLaravelReactI18n();

  return (
    <BaseTooltip delayDuration={700} open={isTooltipEnabled ? undefined : false}>
      <BaseTooltipTrigger className="cursor-default">
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
