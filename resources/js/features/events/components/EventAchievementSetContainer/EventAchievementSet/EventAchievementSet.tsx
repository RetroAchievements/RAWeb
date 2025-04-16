import { AnimatePresence } from 'motion/react';
import * as motion from 'motion/react-m';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementsListItem } from '@/common/components/AchievementsListItem';
import type { AchievementSortOrder } from '@/common/models';
import { eventAchievementTimeStatus } from '@/common/utils/eventAchievementTimeStatus';
import { getEventAchievementTimeStatus } from '@/common/utils/getEventAchievementTimeStatus';
import { sortAchievements } from '@/common/utils/sortAchievements';

import { EventAchievementSection } from './EventAchievementSection';

interface EventAchievementSetProps {
  achievements: App.Platform.Data.Achievement[];
  currentSort: AchievementSortOrder;
  playersTotal: number;

  /**
   * Wherever possible, map stuff onto `achievement`.
   * The less smart this component is, the easier it'll be to maintain long-term.
   * Only pick unique fields from this prop, such as `activeThrough`.
   */
  eventAchievements?: App.Platform.Data.EventAchievement[];
}

export const EventAchievementSet: FC<EventAchievementSetProps> = ({
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
    [eventAchievementTimeStatus.active]: t('Current'),
    [eventAchievementTimeStatus.expired]: t('Previous'),
    [eventAchievementTimeStatus.upcoming]: t('Upcoming'),
    [eventAchievementTimeStatus.future]: t('Future'),
    [eventAchievementTimeStatus.evergreen]: t('Evergreen'),
  };

  // Initialize groups to avoid undefined checks later.
  for (const statusCode of Object.keys(statusTitles)) {
    groupedAchievements[Number(statusCode)] = [];
  }

  // Sort achievements into their respective groups.
  for (const achievement of sortedAchievements) {
    const status = getEventAchievementTimeStatus(achievement, eventAchievements);
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
              achievementCount={items.length}
              isInitiallyOpened={Number(status) !== eventAchievementTimeStatus.future}
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
