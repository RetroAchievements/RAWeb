import { useAtomValue } from 'jotai';
import { AnimatePresence } from 'motion/react';
import * as motion from 'motion/react-m';
import { type FC, useMemo } from 'react';

import { AchievementsListItem } from '@/common/components/AchievementsListItem';
import { cn } from '@/common/utils/cn';
import { sortAchievements } from '@/common/utils/sortAchievements';
import {
  currentAchievementSortAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
} from '@/features/games/state/games.atoms';
import { filterAchievements } from '@/features/games/utils/filterAchievements';

import { AchievementSetCredits } from '../../AchievementSetCredits';
import { GameAchievementSetHeader } from './GameAchievementSetHeader';
import { GameAchievementSetToolbar } from './GameAchievementSetToolbar';

interface GameAchievementSetProps {
  achievements: App.Platform.Data.Achievement[];
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
  isOnlySetForGame: boolean;
}

export const GameAchievementSet: FC<GameAchievementSetProps> = ({
  achievements,
  gameAchievementSet,
  isOnlySetForGame,
}) => {
  const currentAchievementSort = useAtomValue(currentAchievementSortAtom);
  const isLockedOnlyFilterEnabled = useAtomValue(isLockedOnlyFilterEnabledAtom);
  const isMissableOnlyFilterEnabled = useAtomValue(isMissableOnlyFilterEnabledAtom);

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

  const isLargeList = sortedAchievements.length > 50;

  return (
    <div className="flex flex-col gap-2.5">
      <div
        className={cn(
          'flex w-full flex-col gap-2 rounded bg-embed px-2 pb-1 pt-2',
          'light:border light:border-embed-highlight light:bg-neutral-50',
        )}
      >
        <div className="flex items-center justify-between">
          <GameAchievementSetHeader
            gameAchievementSet={gameAchievementSet}
            isOnlySetForGame={isOnlySetForGame}
            isOpen={true}
          />
        </div>

        <AchievementSetCredits />
      </div>

      <GameAchievementSetToolbar
        lockedAchievementsCount={lockedAchievements.length}
        missableAchievementsCount={missableAchievements.length}
        unlockedAchievementsCount={unlockedAchievements.length}
      />

      <div className="relative">
        <AnimatePresence mode="popLayout" initial={false}>
          <motion.ul
            key={`${currentAchievementSort}-${isLockedOnlyFilterEnabled}-${isMissableOnlyFilterEnabled}`}
            className="flex flex-col gap-2.5"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.15 }}
          >
            {filteredAndSortedAchievements.map((achievement, index) => (
              <AchievementsListItem
                key={`ach-${achievement.id}`}
                achievement={achievement}
                index={index}
                isLargeList={isLargeList}
                playersTotal={gameAchievementSet.achievementSet.playersTotal}
              />
            ))}
          </motion.ul>
        </AnimatePresence>
      </div>
    </div>
  );
};
