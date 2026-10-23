import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { FaFileArchive } from 'react-icons/fa';

import { Timeline, TimelineItem } from '@/common/components/Timeline';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { cn } from '@/common/utils/cn';
import { getShouldAchievementSessionBeVisible } from '@/common/utils/getShouldAchievementSessionBeVisible';

import { ClientLabel } from './ClientLabel';
import { HashLabel } from './HashLabel';
import { PlaytimeLabel } from './PlaytimeLabel';
import { SessionTimelineEvent } from './SessionTimelineEvent';

dayjs.extend(utc);

interface UserGameActivityTimelineProps {
  activity: App.Platform.Data.PlayerGameActivity;
  isOnlyShowingAchievementSessions: boolean;

  maxSessionsAroundTicket?: number;
  reportedAchievementId?: number;
}

export const UserGameActivityTimeline: FC<UserGameActivityTimelineProps> = ({
  activity,
  isOnlyShowingAchievementSessions,
  maxSessionsAroundTicket,
  reportedAchievementId,
}) => {
  const { formatDate } = useFormatDate();

  const { sessions } = activity;

  const visibleSessions = sessions.filter((session) => {
    // If it's a manual unlock session but there are no events in the session,
    // then there's nothing we can show.
    if (session.type === 'manual-unlock' && !session.events.length) {
      return false;
    }

    return getShouldAchievementSessionBeVisible(session, isOnlyShowingAchievementSessions);
  });

  let displayedSessions = visibleSessions;
  if (maxSessionsAroundTicket) {
    const ticketSessionIndex = visibleSessions.findIndex((session) =>
      session.events.some((event) => event.type === 'ticket-created'),
    );

    displayedSessions = visibleSessions.slice(
      Math.max(0, ticketSessionIndex - maxSessionsAroundTicket),
      ticketSessionIndex + maxSessionsAroundTicket + 1,
    );
  }

  return (
    <div className="text-gray-200">
      <Timeline>
        {displayedSessions.map((session, index) => (
          <TimelineItem key={`session-${index}`} label={formatDate(session.startTime, 'lll')}>
            {session.type !== 'ticket-created' ? <SessionHeader session={session} /> : null}

            <SessionEvents
              reportedAchievementId={reportedAchievementId}
              session={session}
              sessionIndex={index}
            />
          </TimelineItem>
        ))}
      </Timeline>
    </div>
  );
};

interface SessionHeaderProps {
  session: App.Platform.Data.PlayerGameActivitySession;
}

const SessionHeader: FC<SessionHeaderProps> = ({ session }) => {
  return (
    <div
      data-testid="session-header"
      className={cn(
        'flex flex-col gap-1.5 rounded-t-lg border-b p-3',
        'border-neutral-700 bg-neutral-800 light:border-neutral-200 light:bg-neutral-100',
      )}
    >
      <div className="flex w-full flex-col gap-1.5 md:flex-row md:items-start md:justify-between md:gap-4">
        <ClientLabel session={session} />
        <PlaytimeLabel session={session} />
      </div>

      {session.type === 'player-session' ? (
        <div
          data-testid="rom-info"
          className={cn(
            'flex items-start gap-1.5',
            !session.gameHash
              ? 'text-neutral-500 light:text-neutral-400'
              : 'light:text-neutral-900',
          )}
        >
          <FaFileArchive className="mt-0.5 size-4 min-w-5" />
          <HashLabel session={session} />
        </div>
      ) : null}
    </div>
  );
};

interface SessionEventsProps {
  reportedAchievementId?: number;
  session: App.Platform.Data.PlayerGameActivitySession;
  sessionIndex: number;
}

const SessionEvents: FC<SessionEventsProps> = ({
  reportedAchievementId,
  session,
  sessionIndex,
}) => {
  return (
    <div>
      <ol className="flex flex-col">
        {session.events.map((event, eventIndex) => {
          const currentEventTimestamp = event.when;

          const previousEventTimestamp =
            eventIndex === 0 ? session.startTime : session.events[eventIndex - 1].when;

          const nextEventTimestamp =
            eventIndex === session.events.length - 1 ? null : session.events[eventIndex + 1].when;

          // Grouped events won't have any borders between them and will only show a single timestamp.
          const isGrouped = currentEventTimestamp === nextEventTimestamp;

          // Check if the previous event was grouped with the current event. If it was and we're
          // rendering a bunch of stuff in a group, then we'll only show a timestamp on the row
          // for when the group starts.
          const isPreviousGrouped =
            eventIndex !== 0 && previousEventTimestamp === currentEventTimestamp;

          const isReportedAchievementUnlock =
            event.type === 'unlock' &&
            reportedAchievementId !== undefined &&
            event.achievement?.id === reportedAchievementId;

          const isOutlined = isReportedAchievementUnlock || event.type === 'ticket-created';

          // Same-second manual unlocks granted by different users share the same player session.
          // Any other manual unlocker in the session still needs to be named on the row itself.
          const isUnlockerInSessionHeader =
            session.type === 'manual-unlock' &&
            !!event.unlocker &&
            event.unlocker.displayName === session.events[0].unlocker?.displayName;

          return (
            <li
              key={`sessionEvent-${sessionIndex}-${eventIndex}`}
              className={cn(
                isGrouped && !isOutlined ? 'px-3 pt-3' : 'px-3 pb-2.5 pt-3',

                eventIndex !== session.events.length - 1 && !isGrouped
                  ? 'border-b border-neutral-700 light:border-neutral-200'
                  : null,

                isOutlined ? 'rounded-sm outline-2 -outline-offset-2 outline-text' : null,
              )}
            >
              <SessionTimelineEvent
                hasReportedMarker={isReportedAchievementUnlock}
                isPreviousGrouped={isPreviousGrouped}
                isUnlockerInSessionHeader={isUnlockerInSessionHeader}
                sessionEvent={event}
                sessionType={session.type}
                previousEventTimestamp={previousEventTimestamp}
                previousEventKind={
                  eventIndex === 0 ? 'start-session' : session.events[eventIndex - 1].type
                }
              />
            </li>
          );
        })}
      </ol>
    </div>
  );
};
