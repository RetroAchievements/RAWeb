import { type FC, useMemo } from 'react';
import { Trans } from 'react-i18next';

import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';

interface GameAchievementSetHeaderProps {
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
}

export const GameAchievementSetHeader: FC<GameAchievementSetHeaderProps> = ({
  gameAchievementSet,
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
        <img
          src={imageAssetPathUrl}
          alt={gameAchievementSet.title ?? 'Base Set'} // intentionally untranslated
          width={52}
          height={52}
          className="rounded-sm"
        />

        <div className="flex flex-col items-start gap-0">
          {/* Intentionally left untranslated. It would be weird if this title were translated and subset titles weren't. */}
          <span>{title ?? 'Base Set'}</span>

          <span className="text-xs text-text">
            <Trans
              i18nKey="<1>{{achievementsCount, number}}</1> achievements worth <2>{{pointsCount, number}}</2> <3>({{retroPointsCount, number}})</3> points"
              values={{
                achievementsCount: achievements.length,
                pointsCount: totalPoints,
                retroPointsCount: totalPointsWeighted,
              }}
              components={{
                1: <span className="font-bold" />,
                2: <span className="font-bold" />,
                3: <WeightedPointsContainer />,
              }}
            />
          </span>
        </div>
      </div>
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
