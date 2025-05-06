import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';

import { BigStatusLabel } from './BigStatusLabel';
import { Glow } from './Glow';
import { ProgressBar } from './ProgressBar';

interface EventProgressProps {
  event: App.Platform.Data.Event;
  playerGame: App.Platform.Data.PlayerGame | null;
}

export const EventProgress: FC<EventProgressProps> = ({ event, playerGame }) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const eventAwards = event.eventAwards ?? [];

  const hasUnlockedAnyAchievements = !!playerGame?.achievementsUnlocked;
  const hasEarnedAnyAward = !!eventAwards.some((award) => award.earnedAt);
  const areAllAchievementsOnePoint = !!event.eventAchievements?.every(
    (ea) => ea.achievement?.points && ea.achievement.points === 1,
  );

  let totalPoints = 0;
  for (const ea of event.eventAchievements || []) {
    totalPoints += ea.achievement?.points || 0;
  }

  const hasPlayerEarnedAllPoints = playerGame && playerGame.pointsHardcore === totalPoints;
  const hasPlayerEarnedHighestAwardTier = !!eventAwards.sort(
    (a, b) => b.pointsRequired - a.pointsRequired,
  )[0]?.earnedAt;

  const isMastered =
    (eventAwards.length === 0 && hasPlayerEarnedAllPoints) ||
    (eventAwards.length > 0 && hasPlayerEarnedHighestAwardTier);

  const totalAchievements = event.eventAchievements?.length ?? 0;

  return (
    <div className="group relative -mx-5 lg:mx-0">
      {(hasUnlockedAnyAchievements && hasEarnedAnyAward) || isMastered ? (
        <Glow isMastered={isMastered} />
      ) : null}

      <div className="relative border border-embed-highlight bg-embed px-5 pb-5 pt-3.5 light:border-neutral-200 light:bg-white lg:rounded">
        <div className="mb-2">
          <p className="sr-only">{t('Your Progress')}</p>

          {hasUnlockedAnyAchievements ? (
            <BigStatusLabel event={event} isMastered={isMastered} />
          ) : null}

          <p className="mt-2 leading-4">
            {auth ? (
              <>
                {hasUnlockedAnyAchievements ? (
                  <Trans
                    i18nKey="<1>{{earned, number}}</1> of {{total, number}} achievements"
                    components={{ 1: <span className="font-bold" /> }}
                    values={{
                      earned: playerGame.achievementsUnlocked,
                      total: totalAchievements,
                    }}
                  />
                ) : null}

                {!hasUnlockedAnyAchievements ? (
                  <span>{t("You haven't unlocked any achievements for this event.")}</span>
                ) : null}
              </>
            ) : (
              <Trans
                i18nKey="<1>You must</1> <2>sign in</2> <3>before you can participate in this event.</3>"
                components={{
                  1: <span />,
                  2: <a className="inline" href={route('login')} />,
                  3: <span />,
                }}
              />
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
