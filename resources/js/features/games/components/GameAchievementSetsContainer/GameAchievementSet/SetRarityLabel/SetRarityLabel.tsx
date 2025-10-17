import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';

interface SetRarityLabelProps {
  pointsTotal: number;
  pointsWeighted: number;
}

export const SetRarityLabel: FC<SetRarityLabelProps> = ({ pointsTotal, pointsWeighted }) => {
  const { t } = useTranslation();

  if (!pointsTotal || !pointsWeighted) {
    return null;
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger>
        <span>
          {'· '}
          {buildGameRarityLabel(pointsTotal, pointsWeighted)}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="flex max-w-80 flex-col gap-1">
        <p className="font-bold">{t('Rarity')}</p>

        <div className="flex flex-col gap-2">
          <p>
            {t(
              'Reflects completion rates for this achievement set across all players. Rarer achievements earn more RetroPoints.',
            )}
          </p>

          <p>{t('This indicator may be inflated by set revisions and player attrition.')}</p>
        </div>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
