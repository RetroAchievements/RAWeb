import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

interface WeightedPointsContainerProps {
  children?: ReactNode;
  isTooltipEnabled?: boolean;
  useVerboseTooltip?: boolean;
}

export const WeightedPointsContainer: FC<WeightedPointsContainerProps> = ({
  children,
  isTooltipEnabled = true,
  useVerboseTooltip = false,
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
          <p className="font-bold">{'RetroPoints'}</p>

          <div className="flex flex-col gap-2 whitespace-normal">
            {useVerboseTooltip ? (
              <p>{t('A type of point adjusted by achievement rarity.')}</p>
            ) : null}

            <p>
              {t(
                'Rarer achievements and achievements with higher point values earn more RetroPoints.',
              )}
            </p>

            {useVerboseTooltip ? (
              <p>{t('This indicator may be inflated by set revisions and player attrition.')}</p>
            ) : null}
          </div>
        </span>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
