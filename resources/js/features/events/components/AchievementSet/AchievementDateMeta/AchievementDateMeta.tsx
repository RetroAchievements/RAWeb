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
  const activeUntil = eventAchievement?.activeUntil;

  let isActive = false;
  let isExpired = false;
  let isUpcoming = false;
  if (eventAchievement && event?.state !== 'evergreen' && activeFrom && activeUntil) {
    const now = dayjs.utc();

    isActive = dayjs.utc(activeFrom).isBefore(now) && dayjs.utc(activeUntil).isAfter(now);
    isExpired = dayjs.utc(activeUntil).isBefore(now);
    isUpcoming = dayjs.utc(activeFrom).isAfter(now);
  }

  if (!unlockedAt && !unlockedHardcoreAt && !isActive && !isExpired && !isUpcoming) {
    return null;
  }

  return (
    <div
      data-testid="date-meta"
      className={cn('gap-x-2 text-[0.63rem] text-neutral-400/70', className)}
    >
      {unlockedAt ? (
        <p>
          {t('Unlocked {{unlockDate}}', {
            unlockDate: formatDate(unlockedHardcoreAt ?? unlockedAt, 'lll'),
          })}
        </p>
      ) : null}

      {isActive && activeUntil ? (
        <p className="text-green-400">
          {t('Active until {{date}}', { date: formatDate(activeUntil, 'll') })}
        </p>
      ) : null}

      {isUpcoming && activeFrom ? (
        <p>{t('Starts {{startDate}}', { startDate: formatDate(activeFrom, 'll') })}</p>
      ) : null}

      {isExpired && activeUntil ? (
        <p>{t('Ended {{endDate}}', { endDate: formatDate(activeUntil, 'll') })}</p>
      ) : null}
    </div>
  );
};
