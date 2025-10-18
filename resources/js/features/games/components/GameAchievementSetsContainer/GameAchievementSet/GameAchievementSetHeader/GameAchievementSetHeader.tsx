import { type FC, useMemo } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { usePageProps } from '@/common/hooks/usePageProps';
import { BASE_SET_LABEL } from '@/features/games/utils/baseSetLabel';

import { GameAchievementSetProgress } from '../GameAchievementSetProgress';
import { SetRarityLabel } from '../SetRarityLabel';
import { PlayerGameProgressLabel } from './PlayerGameProgressLabel';

interface GameAchievementSetHeaderProps {
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
}

export const GameAchievementSetHeader: FC<GameAchievementSetHeaderProps> = ({
  gameAchievementSet,
}) => {
  const { isViewingPublishedAchievements } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { achievementSet, title } = gameAchievementSet;
  const { achievements, imageAssetPathUrl } = achievementSet;

  const { pointsTotal, pointsWeighted } = useMemo(
    () => getAchievementStats(achievements),
    [achievements],
  );

  return (
    <div className="relative flex w-full items-center justify-between text-neutral-300 light:text-neutral-700">
      <div className="flex w-full items-center gap-3">
        <img
          src={imageAssetPathUrl}
          alt={gameAchievementSet.title ?? BASE_SET_LABEL}
          width={52}
          height={52}
          className="mt-0.5 hidden self-start rounded-sm sm:block"
        />

        <div className="flex w-full items-center justify-between">
          <div className="flex flex-col items-start gap-0">
            <span>{title ?? BASE_SET_LABEL}</span>

            {isViewingPublishedAchievements ? (
              <>
                <span className="text-xs text-text">
                  {achievements.length ? (
                    <>
                      <Trans
                        i18nKey="<1>{{achievementsCount, number}}</1> $t(playerGameProgressHardcoreAchievements, {'count': {{achievementsCount}} }) worth <2>{{pointsCount, number}}</2> $t(playerGameProgressPoints, {'count': {{pointsCount}} }) <3>(<4>{{retroPointsCount, number}}</4> <5></5>)</3>"
                        values={{
                          achievementsCount: achievements.length,
                          pointsCount: pointsTotal,
                          retroPointsCount: pointsWeighted,
                        }}
                        components={{
                          1: <span className="font-bold" />,
                          2: <span className="font-bold" />,
                          3: <span className="TrueRatio light:text-neutral-400" />,
                          4: <WeightedPointsContainer />,
                          5: (
                            <SetRarityLabel
                              pointsTotal={pointsTotal}
                              pointsWeighted={pointsWeighted}
                            />
                          ),
                        }}
                      />
                    </>
                  ) : (
                    t('There are no achievements for this set yet.')
                  )}
                </span>

                <PlayerGameProgressLabel achievements={achievements} />
              </>
            ) : (
              <span className="text-xs text-text">
                {achievements.length ? (
                  <Trans
                    i18nKey="<1>{{achievementsCount, number}}</1> unpublished $t(playerGameProgressHardcoreAchievements, {'count': {{achievementsCount}} }) worth <2>{{pointsCount, number}}</2> <3>({{retroPointsCount, number}})</3> $t(playerGameProgressPoints, {'count': {{pointsCount}} })"
                    values={{
                      achievementsCount: achievements.length,
                      pointsCount: pointsTotal,
                      retroPointsCount: pointsWeighted,
                    }}
                    components={{
                      1: <span className="font-bold" />,
                      2: <span className="font-bold" />,
                      3: <WeightedPointsContainer />,
                    }}
                  />
                ) : (
                  t('There are currently no unpublished achievements for this set.')
                )}
              </span>
            )}
          </div>
        </div>
      </div>

      {isViewingPublishedAchievements && achievements.length ? (
        <div className="absolute right-2 top-2 hidden sm:block">
          <GameAchievementSetProgress achievements={achievements} />
        </div>
      ) : null}
    </div>
  );
};

function getAchievementStats(achievements: App.Platform.Data.Achievement[]): {
  pointsTotal: number;
  pointsWeighted: number;
} {
  const stats = {
    pointsTotal: 0,
    pointsWeighted: 0,
  };

  for (const achievement of achievements) {
    const achievementPoints = achievement.points as number;
    const achievementPointsWeighted = achievement.pointsWeighted as number;

    stats.pointsTotal += achievementPoints;
    stats.pointsWeighted += achievementPointsWeighted;
  }

  return stats;
}
