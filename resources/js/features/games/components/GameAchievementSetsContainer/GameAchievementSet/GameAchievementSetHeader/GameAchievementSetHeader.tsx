import { type FC, useMemo } from 'react';
import { Trans } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { cn } from '@/common/utils/cn';

interface GameAchievementSetHeaderProps {
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
  isOnlySetForGame: boolean;
  isOpen: boolean;
}

export const GameAchievementSetHeader: FC<GameAchievementSetHeaderProps> = ({
  gameAchievementSet,
  isOnlySetForGame,
  isOpen,
}) => {
  const { achievementSet, title } = gameAchievementSet;
  const { achievements, imageAssetPathUrl } = achievementSet;

  const { totalPoints, totalPointsWeighted } = useMemo(
    () => getAchievementStats(achievements),
    [achievements],
  );

  return (
    <div className="flex items-center justify-between text-neutral-300 light:text-neutral-700">
      <div className="flex items-center gap-3">
        <img src={imageAssetPathUrl} width={52} height={52} className="rounded-sm" />

        <div className="flex flex-col items-start gap-0">
          {/* Intentionally left untranslated. It would be weird if this title were translated and subset titles weren't. */}
          <span>{title ?? 'Base Set'}</span>

          <span className="text-xs text-text">
            <Trans
              i18nKey="{{achievementsCount, number}} achievements worth {{pointsCount, number}} <1>({{retroPointsCount, number}})</1> points"
              values={{
                achievementsCount: achievements.length,
                pointsCount: totalPoints,
                retroPointsCount: totalPointsWeighted,
              }}
              components={{ 1: <WeightedPointsContainer /> }}
            />
          </span>
        </div>
      </div>

      {!isOnlySetForGame ? (
        <LuChevronDown
          data-testid="chevron"
          className={cn(
            'size-5 transition-transform duration-300',
            isOpen ? 'rotate-180' : 'rotate-0',
          )}
        />
      ) : null}
    </div>
  );
};

function getAchievementStats(achievements: App.Platform.Data.Achievement[]): {
  totalPoints: number;
  totalPointsWeighted: number;
} {
  const stats = {
    totalPoints: 0,
    totalPointsWeighted: 0,
  };

  for (const achievement of achievements) {
    const achievementPoints = achievement.points as number;
    const achievementPointsWeighted = achievement.pointsWeighted as number;

    stats.totalPoints += achievementPoints;
    stats.totalPointsWeighted += achievementPointsWeighted;
  }

  return stats;
}
