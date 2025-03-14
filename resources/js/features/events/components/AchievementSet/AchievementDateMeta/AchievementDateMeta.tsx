import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { useAtomValue } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { eventAtom } from '@/features/events/state/events.atoms';

dayjs.extend(utc);

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
  const event = useAtomValue(eventAtom);

  const { t } = useTranslation();

  const { unlockedAt, unlockedHardcoreAt } = achievement;

  const activeFrom = eventAchievement?.activeFrom;
  const activeThrough = eventAchievement?.activeThrough;

  let isActive = false;
  let isExpired = false;
  let isUpcoming = false;
  if (eventAchievement && event?.state !== 'evergreen' && activeFrom && activeThrough) {
    const now = dayjs.utc();

    isActive = dayjs.utc(activeFrom).isBefore(now) && dayjs.utc(activeThrough).isAfter(now);
    isExpired = dayjs.utc(activeThrough).isBefore(now);
    isUpcoming = dayjs.utc(activeFrom).isAfter(now);
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
        <p className="text-green-400">
          {t('Active until {{date}}', { date: formatDate(activeThrough, 'll') })}
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
