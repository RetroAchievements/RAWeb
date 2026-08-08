import dayjs from 'dayjs';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCalendar, LuCalendarCheck } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { useServerRenderTime } from '@/common/hooks/useServerRenderTime';
import { cn } from '@/common/utils/cn';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';
import { InertiaLink } from '../InertiaLink';

interface ActiveEventsIndicatorProps {
  activeEvents: App.Platform.Data.ActiveEventAchievement[];
}

export const ActiveEventsIndicator: FC<ActiveEventsIndicatorProps> = ({ activeEvents }) => {
  const { t } = useTranslation();
  const { renderedAt } = useServerRenderTime();

  if (!activeEvents.length) {
    return null;
  }

  const hasUnlockedAll = activeEvents.every((activeEvent) => activeEvent.userUnlocked);
  const Icon = hasUnlockedAll ? LuCalendarCheck : LuCalendar;

  const href =
    activeEvents.length === 1
      ? route('event.show', activeEvents[0].eventId)
      : route('achievement.show', activeEvents[0].achievementId);

  const buildTimeLeftLabel = (activeUntil: string): string => {
    const endsAt = dayjs(activeUntil);
    const now = dayjs(renderedAt);

    const hoursLeft = endsAt.diff(now, 'hour');
    if (hoursLeft < 1) {
      return t('Last day');
    }
    if (hoursLeft < 24) {
      return t('eventHoursLeft', { val: hoursLeft, count: hoursLeft });
    }

    const daysLeft = endsAt.diff(now, 'day');
    if (daysLeft < 60) {
      return t('eventDaysLeft', { val: daysLeft, count: daysLeft });
    }

    const monthsLeft = endsAt.diff(now, 'month');

    return t('eventMonthsLeft', { val: monthsLeft, count: monthsLeft });
  };

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <InertiaLink href={href}>
          <div
            data-testid="type-active_event"
            className={cn(
              'type-ind group',
              'cursor-pointer',
              'justify-center',
              'size-7',

              hasUnlockedAll ? 'border-amber-400 text-amber-400' : 'border-transparent',
            )}
          >
            <Icon className="size-4.5" />
          </div>
        </InertiaLink>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <ul className="flex flex-col gap-2">
          {activeEvents.map((activeEvent) => (
            <li className="flex flex-col" key={`active-event-${activeEvent.eventId}`}>
              <span>{activeEvent.eventTitle}</span>
              <span className="text-neutral-400" suppressHydrationWarning={true}>
                {buildTimeLeftLabel(activeEvent.activeUntil)}
              </span>
            </li>
          ))}
        </ul>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
