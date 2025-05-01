import { AnimatePresence } from 'motion/react';
import * as motion from 'motion/react-m';
import type { FC } from 'react';

import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '@/common/components/+vendor/BaseCollapsible';
import { AchievementsListItem } from '@/common/components/AchievementsListItem';
import { useAchievementGroupAnimation } from '@/common/hooks/useAchievementGroupAnimation';
import type { AchievementSortOrder } from '@/common/models';
import { cn } from '@/common/utils/cn';
import { sortAchievements } from '@/common/utils/sortAchievements';

import { GameAchievementSetHeader } from './GameAchievementSetHeader';

interface GameAchievementSetProps {
  achievements: App.Platform.Data.Achievement[];
  currentSort: AchievementSortOrder;
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
  isInitiallyOpened: boolean;
  isOnlySetForGame: boolean;
}

export const GameAchievementSet: FC<GameAchievementSetProps> = ({
  achievements,
  currentSort,
  gameAchievementSet,
  isInitiallyOpened,
  isOnlySetForGame,
}) => {
  const { childContainerRef, contentRef, isInitialRender, isOpen, setIsOpen } =
    useAchievementGroupAnimation({ isInitiallyOpened });

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
          <BaseCollapsible open={isOpen} onOpenChange={setIsOpen} disabled={isOnlySetForGame}>
            <BaseCollapsibleTrigger className="w-full">
              <GameAchievementSetHeader
                gameAchievementSet={gameAchievementSet}
                isOnlySetForGame={isOnlySetForGame}
                isOpen={isOpen}
              />
            </BaseCollapsibleTrigger>

            <BaseCollapsibleContent forceMount>
              <div
                ref={contentRef}
                className={cn(
                  !isInitiallyOpened && isInitialRender.current ? 'h-0 overflow-hidden' : null,
                )}
              >
                <div className="relative pt-2.5">
                  <ul ref={childContainerRef} className="flex flex-col gap-2.5">
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
              </div>
            </BaseCollapsibleContent>
          </BaseCollapsible>
        </motion.li>
      </motion.ul>
    </AnimatePresence>
  );
};
