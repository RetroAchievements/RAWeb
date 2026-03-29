import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { usePageProps } from '@/common/hooks/usePageProps';

export const ProximityAchievements: FC = () => {
  const {
    achievement,
    areAllAchievementsOnePoint,
    isEventGame,
    promotedAchievementCount,
    backingGame,
    gameAchievementSet,
    proximityAchievements,
  } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const { formatPercentage } = useFormatPercentage();

  if (!proximityAchievements?.length) {
    return null;
  }

  const viewAllHref = buildViewAllHref(achievement, backingGame, gameAchievementSet);

  return (
    <div>
      <h2 className="mb-0 border-0 text-lg font-semibold">
        {isEventGame ? t('More from this event') : t('More from this set')}
      </h2>

      <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
        <ol className="flex flex-col gap-0.5 py-1">
          {proximityAchievements.map((proximityAchievement) => {
            const points = proximityAchievement.points!;
            const achievementHref = route('achievement2.show', {
              achievement: proximityAchievement.id,
            });

            // Hide stats entirely for upcoming event achievements.
            const isUpcomingEventAchievement =
              isEventGame && Number(proximityAchievement.unlockPercentage ?? 0) === 0;
            const shouldShowPoints = !(isEventGame && areAllAchievementsOnePoint);

            return (
              <li key={`proximity-${proximityAchievement.id}`}>
                <InertiaLink
                  href={achievementHref}
                  className="group flex w-full items-center gap-3 rounded-lg p-2 pl-3 hover:bg-neutral-800/50 focus-visible:bg-neutral-800/50 light:hover:bg-neutral-100 light:focus-visible:bg-neutral-100"
                  prefetch="desktop-hover-only"
                >
                  <AchievementAvatar
                    {...proximityAchievement}
                    displayLockedStatus="auto"
                    hasTooltip={false}
                    shouldLink={false}
                    showLabel={false}
                    size={36}
                  />

                  <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                    <p className="max-w-fit truncate text-link group-hover:text-link-hover">
                      {proximityAchievement.title}
                    </p>

                    <p className="truncate text-2xs text-text">
                      {proximityAchievement.description}
                    </p>

                    {!isUpcomingEventAchievement ? (
                      <span className="text-2xs text-neutral-400 light:text-neutral-500">
                        {shouldShowPoints ? (
                          <>
                            {t('{{val, number}} points', { val: points, count: points })}
                            {' · '}
                          </>
                        ) : null}

                        {formatPercentage(Number(proximityAchievement.unlockPercentage ?? 0), {
                          minimumFractionDigits: 1,
                          maximumFractionDigits: 1,
                        })}
                      </span>
                    ) : null}
                  </div>

                  {buildUnlockCheckIcon(
                    proximityAchievement,
                    t('Unlocked'),
                    t('Unlocked in hardcore'),
                  )}
                </InertiaLink>
              </li>
            );
          })}
        </ol>

        {promotedAchievementCount > 0 ? (
          <div className="flex justify-center p-1">
            <InertiaLink href={viewAllHref} className="text-xs" prefetch="desktop-hover-only">
              {t('View all {{count}} achievements', { count: promotedAchievementCount })}
            </InertiaLink>
          </div>
        ) : null}
      </div>
    </div>
  );
};

function buildUnlockCheckIcon(
  achievement: App.Platform.Data.Achievement,
  unlockLabel: string,
  hardcoreUnlockLabel: string,
): ReactNode {
  if (achievement.unlockedHardcoreAt) {
    return (
      <LuCheck
        aria-label={hardcoreUnlockLabel}
        data-testid={`unlock-check-${achievement.id}`}
        className="size-4 shrink-0 text-[gold] light:text-amber-500"
      />
    );
  }

  if (achievement.unlockedAt) {
    return (
      <LuCheck
        aria-label={unlockLabel}
        data-testid={`unlock-check-${achievement.id}`}
        className="size-4 shrink-0 text-neutral-400 light:text-neutral-700"
      />
    );
  }

  return null;
}

function buildViewAllHref(
  achievement: App.Platform.Data.Achievement,
  backingGame: App.Platform.Data.Game | null,
  gameAchievementSet: App.Platform.Data.GameAchievementSet | null,
): string {
  // For subsets, link to the backing game with the set ID as a query param.
  if (backingGame && gameAchievementSet) {
    return route('game.show', {
      game: backingGame.id,
      _query: { set: gameAchievementSet.achievementSet.id },
    });
  }

  // For normal games, just link to the game page.
  return route('game.show', { game: (achievement.game as App.Platform.Data.Game).id });
}
