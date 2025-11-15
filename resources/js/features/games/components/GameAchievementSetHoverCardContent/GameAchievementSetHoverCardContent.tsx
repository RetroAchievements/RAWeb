import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseHoverCardContent } from '@/common/components/+vendor/BaseHoverCard';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { BASE_SET_LABEL } from '../../utils/baseSetLabel';

interface GameAchievementSetHoverCardContentProps {
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
}

export const GameAchievementSetHoverCardContent: FC<GameAchievementSetHoverCardContentProps> = ({
  gameAchievementSet,
}) => {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  const { achievementSet, title } = gameAchievementSet;
  const {
    achievementsFirstPublishedAt,
    achievementsPublished,
    imageAssetPathUrl,
    pointsTotal,
    pointsWeighted,
  } = achievementSet;

  return (
    <BaseHoverCardContent
      side="top"
      className="w-[400px] max-w-[400px] border border-embed-highlight bg-box-bg p-2"
    >
      <div className="flex gap-2">
        <img src={imageAssetPathUrl} alt={title ?? BASE_SET_LABEL} className="size-24 rounded-sm" />

        <div className="flex flex-col">
          <p
            className={cn(
              'line-clamp-2 font-bold',
              title && title.length > 24 ? 'mb-1 text-sm leading-4' : '-mt-0.5 text-lg leading-6',
            )}
          >
            {title ?? BASE_SET_LABEL}
          </p>

          <p className="flex gap-1">
            <span className="font-bold">{t('Achievements:', { nsSeparator: null })}</span>
            {formatNumber(achievementsPublished)}
          </p>

          <p className="flex gap-1">
            <span className="font-bold">{t('Points:', { nsSeparator: null })}</span>
            {formatNumber(pointsTotal)}
          </p>

          <p className="flex gap-1">
            <span className="font-bold">{t('RetroPoints:', { nsSeparator: null })}</span>
            {pointsWeighted ? (
              <span>
                {formatNumber(pointsWeighted)} {'('}
                {t('{{val}} Rarity', {
                  val: buildGameRarityLabel(pointsTotal, pointsWeighted),
                })}
                {')'}
              </span>
            ) : (
              t('None yet')
            )}
          </p>

          <p className="flex gap-1">
            <span className="font-bold">{t('First Published:', { nsSeparator: null })}</span>
            {achievementsFirstPublishedAt
              ? formatDate(achievementsFirstPublishedAt, 'll')
              : t('Unknown')}
          </p>
        </div>
      </div>
    </BaseHoverCardContent>
  );
};
