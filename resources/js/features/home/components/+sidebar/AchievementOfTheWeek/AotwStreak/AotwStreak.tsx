import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

export const AotwStreak: FC = () => {
  const { achievementOfTheWeek } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (!achievementOfTheWeek?.achievementOfTheWeekProgress) {
    return null;
  }

  const { achievementOfTheWeekProgress } = achievementOfTheWeek;
  const { streakLength, hasCurrentWeek, hasActiveStreak } = achievementOfTheWeekProgress;

  // If they've unlocked this week.
  if (hasCurrentWeek) {
    return (
      <div data-testid="aotw-progress" className="bg-neutral-950 px-2 py-1.5">
        <p className="text-center text-xs text-neutral-300">
          {streakLength > 1
            ? t('Unlocked. {{weeks, number}} weeks in a row!', { weeks: streakLength })
            : t('Unlocked.')}
        </p>
      </div>
    );
  }

  // If they have a streak going but haven't done this week yet.
  if (hasActiveStreak) {
    return (
      <div data-testid="aotw-progress" className="bg-neutral-950 px-2 py-1.5">
        <p className="text-center text-2xs text-neutral-500">
          {t('Extend your {{weeks, number}} week streak!', { weeks: streakLength })}
        </p>
      </div>
    );
  }

  // They haven't unlocked this week and don't have an active streak.
  return null;
};
