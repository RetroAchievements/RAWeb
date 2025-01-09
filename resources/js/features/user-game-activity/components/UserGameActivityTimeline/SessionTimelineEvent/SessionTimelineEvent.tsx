import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';

import { formatDate } from '@/common/utils/l10n/formatDate';

import { RichPresenceEventContent } from './RichPresenceEventContent';
import { UnlockEventContent } from './UnlockEventContent';

dayjs.extend(utc);

interface SessionTimelineEventProps {
  isPreviousGrouped: boolean;
  previousEventTimestamp: string | null;
  previousEventKind: App.Enums.PlayerGameActivityEventType | 'start-session';
  sessionEvent: App.Platform.Data.PlayerGameActivityEvent;
  sessionType: App.Enums.PlayerGameActivitySessionType;
}

export const SessionTimelineEvent: FC<SessionTimelineEventProps> = ({
  isPreviousGrouped,
  previousEventKind,
  previousEventTimestamp,
  sessionEvent,
  sessionType,
}) => {
  const eventTime = formatDate(dayjs.utc(sessionEvent.when), 'LTS');

  return (
    <div className="flex flex-col gap-1 md:flex-row md:items-center md:gap-5">
      <p className="min-w-[88px] light:text-neutral-600">{isPreviousGrouped ? '' : eventTime}</p>

      {sessionEvent.type === 'unlock' && sessionEvent.achievement ? (
        <UnlockEventContent
          previousEventKind={previousEventKind}
          sessionEvent={sessionEvent}
          sessionType={sessionType}
          whenPrevious={previousEventTimestamp}
        />
      ) : null}

      {sessionEvent.type === 'rich-presence' && sessionEvent.description ? (
        <RichPresenceEventContent label={sessionEvent.description} />
      ) : null}
    </div>
  );
};
