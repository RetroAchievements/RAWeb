import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementSortButton } from '@/common/components/AchievementSortButton';
import { EmptyState } from '@/common/components/EmptyState';
import type { AchievementSortOrder } from '@/common/models';

import { EventAchievementSet } from './EventAchievementSet';

/**
 * Events should only have a single achievement set.
 */

interface EventAchievementSetContainerProps {
  event: App.Platform.Data.Event;
}

export const EventAchievementSetContainer: FC<EventAchievementSetContainerProps> = ({ event }) => {
  const { t } = useTranslation();

  const [currentSort, setCurrentSort] = useState<AchievementSortOrder>(
    event.state! === 'evergreen' ? 'displayOrder' : 'active',
  );

  if (!event.eventAchievements?.length) {
    return (
      <div className="rounded bg-embed">
        <EmptyState shouldShowImage={false}>
          {t("There aren't any achievements for this event.")}
        </EmptyState>
      </div>
    );
  }

  const achievements = mapEventAchievementsToAchievements(event.eventAchievements);

  return (
    <div data-testid="event-achievement-sets" className="flex flex-col gap-2">
      <div className="flex w-full justify-between">
        <AchievementSortButton
          value={currentSort}
          onChange={(newValue) => setCurrentSort(newValue)}
          includeActiveOption={event.state !== 'evergreen'}
        />
      </div>

      <EventAchievementSet
        achievements={achievements}
        currentSort={currentSort}
        eventAchievements={event.eventAchievements}
        playersTotal={event.legacyGame?.playersTotal ?? 0}
      />
    </div>
  );
};

function mapEventAchievementsToAchievements(
  eventAchievements: App.Platform.Data.EventAchievement[],
): App.Platform.Data.Achievement[] {
  const validAchievements = eventAchievements.filter(
    (
      a,
    ): a is App.Platform.Data.EventAchievement & {
      achievement: App.Platform.Data.Achievement;
    } => a.achievement !== undefined && a.achievement !== null,
  );

  return validAchievements.map((va) => ({
    ...va.achievement,
    game: va.sourceAchievement?.game,
  }));
}
