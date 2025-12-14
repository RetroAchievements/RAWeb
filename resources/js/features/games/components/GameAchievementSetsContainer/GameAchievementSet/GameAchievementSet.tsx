import { useAtomValue } from 'jotai';
import { AnimatePresence } from 'motion/react';
import * as motion from 'motion/react-m';
import { type FC, useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementsListItem } from '@/common/components/AchievementsListItem';
import { useIsHydrated } from '@/common/hooks/useIsHydrated';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { sortAchievements } from '@/common/utils/sortAchievements';
import { sortLeaderboards } from '@/common/utils/sortLeaderboards';
import { useAchievementGrouping } from '@/features/games/hooks/useAchievementGrouping';
import {
  currentListViewAtom,
  currentPlayableListSortAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
  userAchievementListChangeCounterAtom,
} from '@/features/games/state/games.atoms';
import { filterAchievements } from '@/features/games/utils/filterAchievements';
import { UNGROUPED_BUCKET_ID } from '@/features/games/utils/UNGROUPED_BUCKET_ID';

import { AchievementSetCredits } from '../../AchievementSetCredits';
import { BeatenCreditDialog } from '../../BeatenCreditDialog';
import { LeaderboardsListItem } from '../../LeaderboardsListItem';
import { AchievementGroupSection } from './AchievementGroupSection';
import { GameAchievementSetHeader } from './GameAchievementSetHeader';
import { GameAchievementSetProgress } from './GameAchievementSetProgress';
import { GameAchievementSetToolbar } from './GameAchievementSetToolbar';

interface GameAchievementSetProps {
  achievements: App.Platform.Data.Achievement[];
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
}

export const GameAchievementSet: FC<GameAchievementSetProps> = ({
  achievements,
  gameAchievementSet,
}) => {
  const { allLeaderboards, auth, isViewingPublishedAchievements, numLeaderboards } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const currentAchievementSort = useAtomValue(currentPlayableListSortAtom);
  const currentListView = useAtomValue(currentListViewAtom);
  const isLockedOnlyFilterEnabled = useAtomValue(isLockedOnlyFilterEnabledAtom);
  const isMissableOnlyFilterEnabled = useAtomValue(isMissableOnlyFilterEnabledAtom);
  const userAchievementListChangeCounter = useAtomValue(userAchievementListChangeCounterAtom);

  const lockedAchievements = achievements.filter((a) => !a.unlockedAt);
  const missableAchievements = achievements.filter((a) => a.type === 'missable');
  const unlockedAchievements = achievements.filter((a) => !!a.unlockedAt);

  const sortedAchievements = useMemo(
    () => sortAchievements(achievements, currentAchievementSort),
    [achievements, currentAchievementSort],
  );

  const filteredAndSortedAchievements = useMemo(
    () =>
      filterAchievements(sortedAchievements, {
        showLockedOnly:
          !!lockedAchievements.length && !!unlockedAchievements.length && isLockedOnlyFilterEnabled,
        showMissableOnly: !!missableAchievements.length && isMissableOnlyFilterEnabled,
      }),
    [
      isLockedOnlyFilterEnabled,
      isMissableOnlyFilterEnabled,
      lockedAchievements.length,
      missableAchievements.length,
      sortedAchievements,
      unlockedAchievements.length,
    ],
  );

  const sortedLeaderboards = useMemo(
    () => (allLeaderboards ? sortLeaderboards(allLeaderboards, currentAchievementSort) : []),
    [allLeaderboards, currentAchievementSort],
  );

  const isLargeAchievementsList = sortedAchievements.length > 50;
  const isLargeLeaderboardsList = numLeaderboards > 50;

  /**
   * Limit SSR to the first 20 achievements, and then show all when hydration kicks in.
   * This dramatically reduces the amount of HTML that needs to be sent through Node.js
   * and ultimately reconciled.
   */
  const isHydrated = useIsHydrated();
  const achievementsToRender = isHydrated
    ? filteredAndSortedAchievements
    : filteredAndSortedAchievements.slice(0, 20);

  const { achievementGroups, bucketedAchievements, hasGroups, ungroupedAchievementCount } =
    useAchievementGrouping({
      allAchievements: achievements,
      ssrLimitedAchievements: achievementsToRender,
      rawAchievementGroups: gameAchievementSet.achievementSet.achievementGroups,
    });

  /**
   * It's also important to reserve space for the remaining achievements that aren't
   * rendered yet during SSR so the height of the achievement list itself doesn't change
   * during hydration. If the height changes, we get a layout shift, which adversely
   * affects our Core Web Vitals.
   */
  const remainingAchievementsCount =
    filteredAndSortedAchievements.length - achievementsToRender.length;

  return (
    <div className="flex flex-col gap-2.5">
      <div
        className={cn(
          'flex w-full flex-col gap-2 rounded bg-embed p-2 light:bg-white',
          'light:border light:border-neutral-200',
        )}
      >
        <div className="flex items-center justify-between">
          <GameAchievementSetHeader gameAchievementSet={gameAchievementSet} />
        </div>

        {auth?.user && achievements.length ? (
          <div className="my-2 flex justify-center sm:hidden">
            <GameAchievementSetProgress
              achievements={achievements}
              gameAchievementSet={gameAchievementSet}
            />
          </div>
        ) : null}

        <AchievementSetCredits />
      </div>

      {achievements.length || numLeaderboards > 0 ? (
        <GameAchievementSetToolbar
          lockedAchievementsCount={lockedAchievements.length}
          missableAchievementsCount={missableAchievements.length}
          unlockedAchievementsCount={unlockedAchievements.length}
        />
      ) : null}

      <div className="relative">
        <AnimatePresence mode="popLayout" initial={false}>
          <motion.ul
            key={`${currentListView}-${userAchievementListChangeCounter}`}
            className="flex flex-col gap-2.5"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.15 }}
          >
            {currentListView === 'achievements' ? (
              <>
                {hasGroups && bucketedAchievements ? (
                  <>
                    {achievementGroups.map((group) => {
                      const groupAchievements = bucketedAchievements[group.id];
                      if (groupAchievements.length === 0 && group.achievementCount === 0) {
                        return null;
                      }

                      return (
                        <AchievementGroupSection
                          key={`group-${group.id}`}
                          achievementCount={group.achievementCount}
                          iconUrl={group.badgeUrl ?? undefined}
                          isInitiallyOpened={true}
                          title={group.label}
                        >
                          {groupAchievements.map((achievement, index) => (
                            <AchievementsListItem
                              key={`ach-${achievement.id}`}
                              achievement={achievement}
                              beatenDialogContent={<BeatenCreditDialog />}
                              index={index}
                              isLargeList={isLargeAchievementsList}
                              shouldShowAuthor={!isViewingPublishedAchievements}
                              playersTotal={gameAchievementSet.achievementSet.playersTotal}
                            />
                          ))}
                        </AchievementGroupSection>
                      );
                    })}

                    {/* Render ungrouped achievements at the end if any exist. */}
                    {ungroupedAchievementCount > 0 ? (
                      <AchievementGroupSection
                        achievementCount={ungroupedAchievementCount}
                        iconUrl={gameAchievementSet.achievementSet.ungroupedBadgeUrl ?? undefined}
                        isInitiallyOpened={true}
                        title={t('otherAchievements')}
                      >
                        {bucketedAchievements[UNGROUPED_BUCKET_ID]?.map((achievement, index) => (
                          <AchievementsListItem
                            key={`ach-${achievement.id}`}
                            achievement={achievement}
                            beatenDialogContent={<BeatenCreditDialog />}
                            index={index}
                            isLargeList={isLargeAchievementsList}
                            shouldShowAuthor={!isViewingPublishedAchievements}
                            playersTotal={gameAchievementSet.achievementSet.playersTotal}
                          />
                        ))}
                      </AchievementGroupSection>
                    ) : null}

                    {/* This placeholder reserves space during SSR to prevent a layout shift. */}
                    {!isHydrated && remainingAchievementsCount > 0 ? (
                      <li
                        aria-hidden="true"
                        style={{ height: remainingAchievementsCount * 96 - 10 }}
                        data-testid="invisible-placeholder"
                      />
                    ) : null}
                  </>
                ) : (
                  <>
                    {achievementsToRender.map((achievement, index) => (
                      <AchievementsListItem
                        key={`ach-${achievement.id}`}
                        achievement={achievement}
                        beatenDialogContent={<BeatenCreditDialog />}
                        index={index}
                        isLargeList={isLargeAchievementsList}
                        shouldShowAuthor={!isViewingPublishedAchievements}
                        playersTotal={gameAchievementSet.achievementSet.playersTotal}
                      />
                    ))}

                    {/* This placeholder reserves space during SSR to prevent a layout shift. */}
                    {!isHydrated && remainingAchievementsCount > 0 ? (
                      <li
                        aria-hidden="true"
                        style={{ height: remainingAchievementsCount * 96 - 10 }}
                        data-testid="invisible-placeholder"
                      />
                    ) : null}
                  </>
                )}
              </>
            ) : null}

            {currentListView === 'leaderboards' ? (
              <>
                {sortedLeaderboards.map((leaderboard, index) => (
                  <LeaderboardsListItem
                    key={`lbd-${leaderboard.id}`}
                    index={index}
                    isLargeList={isLargeLeaderboardsList}
                    leaderboard={leaderboard}
                  />
                ))}
              </>
            ) : null}
          </motion.ul>
        </AnimatePresence>
      </div>
    </div>
  );
};
