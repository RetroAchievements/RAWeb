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
    <BaseTooltip open={isTooltipEnabled ? undefined : false} disableHoverableContent={true}>
      <BaseTooltipTrigger asChild>
        <span className="TrueRatio light:text-neutral-400">{children}</span>
      </BaseTooltipTrigger>

      <BaseTooltipContent asChild>
        <span className="flex max-w-80 flex-col gap-1 text-wrap text-left text-xs">
          {/* Intentionally untranslated, this is a branding term */}
          <span className="font-bold">{'RetroPoints'}</span>

          <span>
            {t(
              'A measurement of rarity and estimated difficulty. Derived from points, number of achievers, and number of players.',
            )}
          </span>
        </span>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
