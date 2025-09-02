import { type FC, useMemo } from 'react';
import { Trans } from 'react-i18next';

import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { usePageProps } from '@/common/hooks/usePageProps';

interface PlayerGameProgressLabelProps {
  achievements: App.Platform.Data.Achievement[];
}

export const PlayerGameProgressLabel: FC<PlayerGameProgressLabelProps> = ({ achievements }) => {
  const { playerGame } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const {
    unlockedHardcoreAchievements,
    unlockedPointsHardcore,
    unlockedPointsSoftcore,
    unlockedPointsWeighted,
    unlockedSoftcoreAchievements,
  } = useMemo(() => getProgressStats(achievements), [achievements]);

  if (!playerGame?.achievementsUnlocked) {
    return null;
  }

  // Don't show a redundant label. A visual for completion/mastery is handled elsewhere.
  if (
    unlockedSoftcoreAchievements.length === achievements.length ||
    unlockedHardcoreAchievements.length === achievements.length
  ) {
    return null;
  }

  return (
    <div className="flex flex-col text-xs text-text">
      {playerGame.achievementsUnlockedHardcore ? (
        <p>
          <Trans
            i18nKey="playerGameProgressHardcore"
            values={{
              achievementsCount: playerGame.achievementsUnlockedHardcore,
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
      ) : null}

      {playerGame.achievementsUnlockedSoftcore ? (
        <p>
          <Trans
            i18nKey="playerGameProgressSoftcore"
            values={{
              achievementsCount: playerGame.achievementsUnlockedSoftcore,
              pointsCount: unlockedPointsSoftcore,
            }}
            components={{
              1: <span className="font-semibold" />,
            }}
          />
        </p>
      ) : null}
    </div>
  );
};

function getProgressStats(achievements: App.Platform.Data.Achievement[]): {
  unlockedHardcoreAchievements: App.Platform.Data.Achievement[];
  unlockedPointsHardcore: number;
  unlockedPointsSoftcore: number;
  unlockedPointsWeighted: number;
  unlockedSoftcoreAchievements: App.Platform.Data.Achievement[];
} {
  const unlockedSoftcoreAchievements = achievements.filter(
    (a) => a.unlockedAt && !a.unlockedHardcoreAt,
  );
  const unlockedHardcoreAchievements = achievements.filter((a) => a.unlockedHardcoreAt);

  let unlockedPointsSoftcore = 0;
  let unlockedPointsHardcore = 0;
  let unlockedPointsWeighted = 0;
  for (const achievement of unlockedSoftcoreAchievements) {
    unlockedPointsSoftcore += achievement.points as number;
  }
  for (const achievement of unlockedHardcoreAchievements) {
    unlockedPointsHardcore += achievement.points as number;
    unlockedPointsWeighted += achievement.pointsWeighted as number;
  }

  return {
    unlockedHardcoreAchievements,
    unlockedPointsHardcore,
    unlockedPointsSoftcore,
    unlockedPointsWeighted,
    unlockedSoftcoreAchievements,
  };
}
