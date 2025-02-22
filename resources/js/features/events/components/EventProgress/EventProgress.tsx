import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { BigStatusLabel } from './BigStatusLabel';
import { Glow } from './Glow';
import { ProgressBar } from './ProgressBar';

interface EventProgressProps {
  event: App.Platform.Data.Event;
  playerGame: App.Platform.Data.PlayerGame | null;
}

export const EventProgress: FC<EventProgressProps> = ({ event, playerGame }) => {
  const { t } = useTranslation();

  const hasUnlockedAnyAchievements = !!playerGame?.achievementsUnlocked;
  const hasEarnedAnyAward = !!event.eventAwards?.some((award) => award.earnedAt);
  const hasEarnedEveryAward = !!event.eventAwards?.every((award) => award.earnedAt);
  const areAllAchievementsOnePoint = !!event.eventAchievements?.every(
    (ea) => ea.achievement?.points && ea.achievement.points === 1,
  );

  let totalPoints = 0;
  for (const ea of event.eventAchievements || []) {
    totalPoints += ea.achievement?.points || 0;
  }

  const totalAchievements = event.eventAchievements?.length ?? 0;

  return (
    <div className="group relative -mx-5 lg:mx-0">
      {hasUnlockedAnyAchievements && hasEarnedAnyAward ? (
        <Glow isMastered={hasEarnedEveryAward} />
      ) : null}

      <div className="relative border border-embed-highlight bg-embed px-5 pb-5 pt-3.5 lg:rounded">
        <div className="mb-2">
          <p className="sr-only">{t('Your Progress')}</p>

          {hasUnlockedAnyAchievements ? <BigStatusLabel event={event} /> : null}

          <p className="mt-2 leading-4">
            {hasUnlockedAnyAchievements ? (
              <Trans
                i18nKey="<1>{{earned, number}}</1> of {{total, number}} achievements"
                components={{ 1: <span className="font-bold" /> }}
                values={{
                  earned: playerGame.achievementsUnlocked,
                  total: totalAchievements,
                }}
              />
            ) : (
              "You haven't unlocked any achievements for this event."
            )}
          </p>

          {hasUnlockedAnyAchievements && !areAllAchievementsOnePoint ? (
            <Trans
              i18nKey="<1>{{earned, number}}</1> of {{total, number}} points"
              components={{ 1: <span className="font-bold" /> }}
              values={{ earned: playerGame.pointsHardcore, total: totalPoints }}
            />
          ) : null}
        </div>

        <ProgressBar
          totalAchievementsCount={totalAchievements}
          numEarnedAchievements={playerGame?.achievementsUnlocked ?? 0}
        />
      </div>
    </div>
  );
};
