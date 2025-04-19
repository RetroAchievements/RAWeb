import dayjs from 'dayjs';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

dayjs.extend(utc);
dayjs.extend(isSameOrBefore);

interface AchievementDateMetaProps {
  achievement: App.Platform.Data.Achievement;

  className?: string;
  eventAchievement?: App.Platform.Data.EventAchievement;
}

export const AchievementDateMeta: FC<AchievementDateMetaProps> = ({
  achievement,
  className,
  eventAchievement,
}) => {
  const { event } = usePageProps<App.Platform.Data.EventShowPageProps>();

  const { t } = useTranslation();

  const { unlockedAt, unlockedHardcoreAt } = achievement;

  const activeFrom = eventAchievement?.activeFrom;
  const activeThrough = eventAchievement?.activeThrough;

  let isActive = false;
  let isExpired = false;
  let isUpcoming = false;
  if (eventAchievement && event?.state !== 'evergreen' && activeFrom && activeThrough) {
    const now = dayjs.utc();
    const activeFrom = dayjs.utc(eventAchievement.activeFrom);
    const activeUntil = dayjs.utc(eventAchievement.activeUntil);

    isActive = activeFrom.isSameOrBefore(now) && now.isBefore(activeUntil);
    isExpired = activeUntil.isSameOrBefore(now);
    isUpcoming = activeFrom.isAfter(now);
  }

  if (!unlockedAt && !unlockedHardcoreAt && !isActive && !isExpired && !isUpcoming) {
    return null;
  }

  return (
    <div
      data-testid="date-meta"
      className={cn('gap-x-2 text-[0.63rem] text-neutral-400/70 light:text-neutral-500', className)}
    >
      {isActive && activeThrough ? (
        <p className="text-green-400 light:text-green-600">
          {t('Active through {{date}}', { date: formatDate(activeThrough, 'll') })}
        </p>
      ) : null}

      {isUpcoming && activeFrom ? (
        <p>{t('Starts {{startDate}}', { startDate: formatDate(activeFrom, 'll') })}</p>
      ) : null}

      {isExpired && activeThrough ? (
        <p className="text-neutral-300 light:text-neutral-800">
          {t('Ended {{endDate}}', { endDate: formatDate(activeThrough, 'll') })}
        </p>
      ) : null}

      {unlockedAt ? (
        <p>
          {t('Unlocked {{unlockDate}}', {
            unlockDate: formatDate(unlockedHardcoreAt ?? unlockedAt, 'lll'),
          })}
        </p>
      ) : null}
    </div>
  );
};
