/* eslint-disable jsx-a11y/no-noninteractive-element-interactions -- this is handled manually */

import { type FC, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck } from 'react-icons/lu';
import { useMedia } from 'react-use';
import { route } from 'ziggy-js';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { useProximityAnimation, VISIBLE_COUNT } from './useProximityAnimation';

export const ProximityAchievements: FC = () => {
  const {
    achievement,
    promotedAchievementCount,
    backingGame,
    gameAchievementSet,
    proximityAchievements,
  } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const { formatPercentage } = useFormatPercentage();

  // On small screens the proximity list isn't in a sidebar, so skip the animation and navigate directly.
  const isSmallScreen = useMedia('(max-width: 1023px)', false);

  const currentIndex = proximityAchievements?.findIndex((a) => a.id === achievement.id) ?? -1;

  const {
    containerRef,
    focusedIndex,
    listRef,
    indicatorRef,
    itemRefs,
    titleRefs,
    handleItemClick,
    handleItemKeyDown,
    handleItemMouseEnter,
    handleItemMouseLeave,
  } = useProximityAnimation({
    currentIndex,
    itemCount: proximityAchievements?.length ?? 0,
    shouldSkipAnimation: isSmallScreen,
  });

  if (!proximityAchievements?.length) {
    return null;
  }

  // If the only entry is the current achievement itself, there's nothing "more" to show.
  if (proximityAchievements.length === 1 && proximityAchievements[0].id === achievement.id) {
    return null;
  }

  const viewAllHref = buildViewAllHref(achievement, backingGame, gameAchievementSet);

  const ssrMaxHeight =
    proximityAchievements.length > VISIBLE_COUNT ? `${54 * VISIBLE_COUNT + 8}px` : undefined;

  return (
    <div>
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('More from this set')}</h2>

      <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
        <div
          ref={containerRef}
          className="overflow-hidden py-1"
          style={{ maxHeight: ssrMaxHeight }}
        >
          <ol ref={listRef} className="relative overflow-visible rounded-lg will-change-transform">
            <div
              ref={indicatorRef}
              data-testid="proximity-indicator"
              className="invisible absolute left-0 z-10 w-1 rounded-full bg-link"
            />

            {proximityAchievements.map((proximityAchievement, index) => {
              const isCurrent = proximityAchievement.id === achievement.id;
              const points = proximityAchievement.points!;
              const achievementHref = route('achievement2.show', {
                achievement: proximityAchievement.id,
              });

              return (
                <li
                  key={`proximity-${proximityAchievement.id}`}
                  ref={(el) => {
                    itemRefs.current[index] = el;
                  }}
                  role={!isCurrent ? 'button' : undefined}
                  tabIndex={focusedIndex === index ? 0 : -1}
                  className={cn(
                    'group flex w-full select-none items-center gap-3 rounded-lg p-2 pl-3',
                    !isCurrent &&
                      'cursor-pointer hover:bg-neutral-800/50 focus-visible:bg-neutral-800/50 light:hover:bg-neutral-100 light:focus-visible:bg-neutral-100',
                  )}
                  onClick={() => {
                    if (!isCurrent) {
                      handleItemClick(index, achievementHref);
                    }
                  }}
                  onKeyDown={(e) => handleItemKeyDown(e, index, achievementHref)}
                  onMouseEnter={() => {
                    if (!isCurrent) {
                      handleItemMouseEnter(achievementHref);
                    }
                  }}
                  onMouseLeave={handleItemMouseLeave}
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
                    <p
                      ref={(el) => {
                        titleRefs.current[index] = el;
                      }}
                      className={cn(
                        'max-w-fit truncate',
                        !isCurrent && 'text-link hover:text-link-hover',
                      )}
                    >
                      {proximityAchievement.title}
                    </p>

                    <p className="truncate text-2xs text-text">
                      {proximityAchievement.description}
                    </p>

                    <span className="text-2xs text-neutral-400 light:text-neutral-500">
                      {t('{{val, number}} points', { val: points, count: points })}
                      {' · '}
                      {formatPercentage(Number(proximityAchievement.unlockPercentage ?? 0), {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1,
                      })}
                    </span>
                  </div>

                  {buildUnlockCheckIcon(
                    proximityAchievement,
                    t('Unlocked'),
                    t('Unlocked in hardcore'),
                  )}
                </li>
              );
            })}
          </ol>
        </div>

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
