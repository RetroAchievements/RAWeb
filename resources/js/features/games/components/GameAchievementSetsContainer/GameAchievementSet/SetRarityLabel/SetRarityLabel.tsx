import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuGem } from 'react-icons/lu';

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
          <LuGem className="inline" />
          {buildGameRarityLabel(pointsTotal, pointsWeighted)}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="flex max-w-80 flex-col gap-1">
        <p className="font-bold">{t('Rarity')}</p>

        <p>
          {t(
            'Reflects completion rates for this achievement set across all players. Rarer achievements earn more RetroPoints.',
          )}
        </p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
