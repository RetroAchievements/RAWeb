import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { GameCreateForumTopicButton } from '@/common/components/GameCreateForumTopicButton';
import { PlayableOfficialForumTopicButton } from '@/common/components/PlayableOfficialForumTopicButton';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { usePageProps } from '@/common/hooks/usePageProps';

interface EventSidebarFullWidthButtonsProps {
  event: App.Platform.Data.Event;
}

export const EventSidebarFullWidthButtons: FC<EventSidebarFullWidthButtonsProps> = ({ event }) => {
  const { auth, can } = usePageProps<App.Platform.Data.EventShowPageProps>();

  const { t } = useTranslation();

  if (!auth?.user && !event.legacyGame?.forumTopicId) {
    return null;
  }

  return (
    <div className="flex flex-col gap-4">
      {event.legacyGame?.forumTopicId ? (
        <div className="flex flex-col gap-2">
          <p className="-mb-1 text-xs text-neutral-300 light:text-neutral-800">
            {t('Essential Resources')}
          </p>

          <PlayableOfficialForumTopicButton game={event.legacyGame!} />
        </div>
      ) : null}

      {can.manageEvents ? (
        <div className="flex flex-col gap-2">
          <p className="-mb-1 text-xs text-neutral-300 light:text-neutral-800">{t('Manage')}</p>

          <PlayableSidebarButton href={`/manage/events/${event.id}`} IconComponent={LuWrench}>
            {t('Event Details')}
          </PlayableSidebarButton>

          {!event.legacyGame?.forumTopicId && can.createGameForumTopic ? (
            <GameCreateForumTopicButton game={event.legacyGame!} />
          ) : null}
        </div>
      ) : null}
    </div>
  );
};
