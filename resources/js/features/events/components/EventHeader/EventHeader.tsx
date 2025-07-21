import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { EndDateChip } from './EndDateChip';
import { IsPlayableChip } from './IsPlayableChip';
import { StartDateChip } from './StartDateChip';

/**
 * TODO this eventually needs to be moved to a shared components module and renamed
 * to something like "PlayableHeader", used on any "playable" show page, such as games,
 * events, and quests. this means this probably needs to be made more composable kinda
 * like a shadcn/ui component, as these things could have wildly different data models.
 */

interface EventHeaderProps {
  event: App.Platform.Data.Event;
}

export const EventHeader: FC<EventHeaderProps> = ({ event }) => {
  const { t } = useTranslation();

  if (!event.legacyGame) {
    return null;
  }

  return (
    <div className="flex flex-col gap-3" data-testid="playable-header">
      <div className="flex gap-4 sm:gap-6">
        <img
          src={event.legacyGame.badgeUrl}
          alt={event.legacyGame.title}
          className="size-16 rounded-sm sm:size-24"
        />

        <div className="-mt-1 flex flex-col gap-4 sm:-mt-1.5">
          <div className="flex flex-col gap-1 sm:gap-0.5">
            <h1 className="text-h3 mb-0 border-b-0 text-lg sm:text-2xl">
              {event.legacyGame.title}
            </h1>

            <span className="flex items-center gap-1 whitespace-nowrap text-xs">
              <img src="/assets/images/system/events.png" alt="icon" width={18} height={18} />
              <span>{t('Event')}</span>
            </span>
          </div>

          <div className="hidden flex-wrap gap-x-2 gap-y-1 text-neutral-300 light:text-neutral-700 sm:flex">
            <IsPlayableChip event={event} />
            <StartDateChip event={event} />
            <EndDateChip event={event} />
          </div>
        </div>
      </div>

      <div className="flex flex-wrap gap-x-2 gap-y-1 text-neutral-300 light:text-neutral-700 sm:hidden">
        <IsPlayableChip event={event} />
        <StartDateChip event={event} />
        <EndDateChip event={event} />
      </div>
    </div>
  );
};
