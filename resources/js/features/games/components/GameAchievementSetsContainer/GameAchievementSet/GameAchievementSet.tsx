import { AnimatePresence } from 'motion/react';
import * as motion from 'motion/react-m';
import type { FC } from 'react';

import { AchievementsListItem } from '@/common/components/AchievementsListItem';
import type { AchievementSortOrder } from '@/common/models';
import { cn } from '@/common/utils/cn';
import { sortAchievements } from '@/common/utils/sortAchievements';

import { AchievementSetCredits } from '../../AchievementSetCredits';
import { GameAchievementSetHeader } from './GameAchievementSetHeader';

interface GameAchievementSetProps {
  achievements: App.Platform.Data.Achievement[];
  currentSort: AchievementSortOrder;
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
  isOnlySetForGame: boolean;
}

export const GameAchievementSet: FC<GameAchievementSetProps> = ({
  achievements,
  currentSort,
  gameAchievementSet,
  isOnlySetForGame,
}) => {
  const sortedAchievements = sortAchievements(achievements, currentSort);
  const isLargeList = sortedAchievements.length > 50;

  return (
    <AnimatePresence mode="wait" initial={false}>
      <motion.ul key={currentSort} className="flex flex-col gap-7">
        <motion.li
          className="flex flex-col gap-2.5"
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0, y: 10 }}
          transition={{
            duration: 0.12,
            delay: 0.03, // Tiny delay to let previous items finish exiting.
          }}
        >
          <div
            className={cn(
              'flex w-full flex-col gap-2 rounded bg-embed px-2 pb-1 pt-2',
              'light:border light:border-embed-highlight light:bg-neutral-50',
            )}
          >
            <GameAchievementSetHeader
              gameAchievementSet={gameAchievementSet}
              isOnlySetForGame={isOnlySetForGame}
              isOpen={true}
            />

            <AchievementSetCredits />
          </div>

          <div className="relative">
            <ul className="flex flex-col gap-2.5">
              {achievements.map((achievement, index) => (
                <AchievementsListItem
                  key={`ach-${achievement.id}`}
                  achievement={achievement}
                  index={index}
                  isLargeList={isLargeList}
                  playersTotal={gameAchievementSet.achievementSet.playersTotal}
                />
              ))}
            </ul>
          </div>
        </motion.li>
      </motion.ul>
    </AnimatePresence>
  );
};
