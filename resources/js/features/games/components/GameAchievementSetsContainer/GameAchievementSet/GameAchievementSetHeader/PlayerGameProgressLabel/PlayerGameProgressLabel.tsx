import type { FC } from 'react';
import { Trans } from 'react-i18next';
import { LuLockOpen } from 'react-icons/lu';

import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';

interface PlayerGameProgressLabelProps {
  achievements: App.Platform.Data.Achievement[];
}

export const PlayerGameProgressLabel: FC<PlayerGameProgressLabelProps> = ({ achievements }) => {
  const {
    unlockedAchievementsCount,
    unlockedCasualAchievements,
    unlockedHardcoreAchievements,
    unlockedPointsCasual,
    unlockedPointsHardcore,
    unlockedPointsWeighted,
  } = getProgressStats(achievements);

  if (!unlockedAchievementsCount) {
    return null;
  }

  // Don't show a redundant label. A visual for completion/mastery is handled elsewhere.
  if (
    unlockedCasualAchievements.length === achievements.length ||
    unlockedHardcoreAchievements.length === achievements.length
  ) {
    return null;
  }

  return (
    <div className="flex flex-col text-xs text-text">
      {unlockedHardcoreAchievements.length ? (
        <>
          <p className="flex items-center gap-1 sm:hidden">
            <LuLockOpen className="size-3.5 text-neutral-300" />
            <Trans
              i18nKey="playerGameProgressHardcoreMobile"
              values={{
                achievementsCount: unlockedHardcoreAchievements.length,
                pointsCount: unlockedPointsHardcore,
                weightedPoints: unlockedPointsWeighted,
              }}
              components={{
                1: <span className="font-semibold" />,
                2: <span className="font-semibold" />,
                3: <WeightedPointsContainer />,
              }}
            />
          </p>

          <p className="hidden sm:block">
            <Trans
              i18nKey="playerGameProgressHardcore"
              values={{
                achievementsCount: unlockedHardcoreAchievements.length,
                pointsCount: unlockedPointsHardcore,
                weightedPoints: unlockedPointsWeighted,
              }}
              components={{
                1: <span className="font-semibold" />,
                2: <span className="font-semibold" />,
                3: <WeightedPointsContainer />,
              }}
            />
          </p>
        </>
      ) : null}

      {unlockedCasualAchievements.length ? (
        <>
          <p className="flex items-center gap-1 sm:hidden">
            <LuLockOpen className="size-3.5 text-neutral-500" />
            <Trans
              i18nKey="playerGameProgressCasualMobile"
              values={{
                achievementsCount: unlockedCasualAchievements.length,
                pointsCount: unlockedPointsCasual,
              }}
              components={{
                1: <span className="font-semibold" />,
              }}
            />
          </p>

          <p className="hidden sm:block">
            <Trans
              i18nKey="playerGameProgressCasual"
              values={{
                achievementsCount: unlockedCasualAchievements.length,
                pointsCount: unlockedPointsCasual,
              }}
              components={{
                1: <span className="font-semibold" />,
              }}
            />
          </p>
        </>
      ) : null}
    </div>
  );
};

function getProgressStats(achievements: App.Platform.Data.Achievement[]): {
  unlockedAchievementsCount: number;
  unlockedCasualAchievements: App.Platform.Data.Achievement[];
  unlockedHardcoreAchievements: App.Platform.Data.Achievement[];
  unlockedPointsCasual: number;
  unlockedPointsHardcore: number;
  unlockedPointsWeighted: number;
} {
  const unlockedAchievementsCount = achievements.filter(
    (ach) => ach.unlockedAt || ach.unlockedHardcoreAt,
  ).length;

  const unlockedCasualAchievements = achievements.filter(
    (a) => a.unlockedAt && !a.unlockedHardcoreAt,
  );
  const unlockedHardcoreAchievements = achievements.filter((a) => a.unlockedHardcoreAt);

  let unlockedPointsCasual = 0;
  let unlockedPointsHardcore = 0;
  let unlockedPointsWeighted = 0;
  for (const achievement of unlockedCasualAchievements) {
    unlockedPointsCasual += achievement.points as number;
  }
  for (const achievement of unlockedHardcoreAchievements) {
    unlockedPointsHardcore += achievement.points as number;
    unlockedPointsWeighted += achievement.pointsWeighted as number;
  }

  return {
    unlockedAchievementsCount,
    unlockedCasualAchievements,
    unlockedHardcoreAchievements,
    unlockedPointsCasual,
    unlockedPointsHardcore,
    unlockedPointsWeighted,
  };
}
