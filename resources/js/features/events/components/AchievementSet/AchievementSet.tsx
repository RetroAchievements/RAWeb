import { AnimatePresence } from 'motion/react';
import * as motion from 'motion/react-m';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import type { AchievementSortOrder } from '../../models';
import { AchievementsListItem } from './AchievementsListItem';
import { EventAchievementSection } from './EventAchievementSection';
import { getStatus, sortAchievements } from './sortAchievements';

interface AchievementSetProps {
  achievements: App.Platform.Data.Achievement[];
  currentSort: AchievementSortOrder;
  playersTotal: number;

  /**
   * Wherever possible, map stuff onto `achievement`.
   * The less smart this component is, the easier it'll be to maintain long-term.
   * Only pick unique fields from this prop, such as `activeUntil`.
   */
  eventAchievements?: App.Platform.Data.EventAchievement[];
}

export const AchievementSet: FC<AchievementSetProps> = ({
  achievements,
  currentSort,
  eventAchievements,
  playersTotal,
}) => {
  const { t } = useTranslation();

  const sortedAchievements = sortAchievements(achievements, currentSort, eventAchievements);
  const isLargeList = sortedAchievements.length > 50;

  // Render achievements list based on current sort order.
  if (currentSort !== 'active') {
    return (
      <AnimatePresence mode="wait" initial={false}>
        <motion.ul key={currentSort} className="flex flex-col gap-2.5">
          {sortedAchievements.map((achievement, index) => (
            <AchievementsListItem
              key={`ach-${achievement.id}`}
              achievement={achievement}
              index={index}
              isLargeList={isLargeList}
              eventAchievement={eventAchievements?.find(
                (ea) => ea.achievement?.id === achievement.id,
              )}
              playersTotal={playersTotal}
            />
          ))}
        </motion.ul>
      </AnimatePresence>
    );
  }

  // Group achievements by status for activity-based sorting.
  const groupedAchievements: Record<number, App.Platform.Data.Achievement[]> = {};

  const statusTitles = {
    0: t('Current'),
    1: t('Previous'),
    2: t('Upcoming'),
    3: t('Evergreen'),
  };

  // Initialize groups to avoid undefined checks later.
  for (const statusCode of Object.keys(statusTitles)) {
    groupedAchievements[Number(statusCode)] = [];
  }

  // Sort achievements into their respective groups.
  for (const achievement of sortedAchievements) {
    const status = getStatus(achievement, eventAchievements);
    groupedAchievements[status].push(achievement);
  }

  return (
    <AnimatePresence mode="wait" initial={false}>
      <motion.ul key={currentSort} className="flex flex-col gap-7">
        {Object.entries(groupedAchievements).map(([status, items]) => {
          if (!items.length) {
            return null;
          }

          return (
            <EventAchievementSection
              key={status}
              title={statusTitles[Number(status) as keyof typeof statusTitles]}
            >
              {items.map((achievement, index) => (
                <AchievementsListItem
                  key={`ach-${achievement.id}`}
                  achievement={achievement}
                  index={index}
                  isLargeList={isLargeList}
                  eventAchievement={eventAchievements?.find(
                    (ea) => ea.achievement?.id === achievement.id,
                  )}
                  playersTotal={playersTotal}
                />
              ))}
            </EventAchievementSection>
          );
        })}
      </motion.ul>
    </AnimatePresence>
  );
};
