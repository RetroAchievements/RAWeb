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
    <BaseTooltip disableHoverableContent={true}>
      <BaseTooltipTrigger>
        <span>
          {'· '}
          {buildGameRarityLabel(pointsTotal, pointsWeighted)}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="flex max-w-80 flex-col gap-1">
        <span className="flex max-w-80 flex-col gap-1 text-wrap text-left text-xs">
          {/* Intentionally untranslated, this is a branding term */}
          <span className="font-bold">{'RetroRatio'}</span>

          <span>{t('The ratio of RetroPoints to points.')}</span>
        </span>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
