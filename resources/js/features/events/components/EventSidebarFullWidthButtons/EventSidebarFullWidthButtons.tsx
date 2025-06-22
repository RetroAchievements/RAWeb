import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { GameCreateForumTopicButton } from '@/common/components/GameCreateForumTopicButton';
import { PlayableOfficialForumTopicButton } from '@/common/components/PlayableOfficialForumTopicButton';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { PlayableSidebarButtonsSection } from '@/common/components/PlayableSidebarButtonsSection';
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
        <PlayableSidebarButtonsSection headingLabel={t('Essential Resources')}>
          <PlayableOfficialForumTopicButton game={event.legacyGame!} />
        </PlayableSidebarButtonsSection>
      ) : null}

      {can.manageEvents ? (
        <PlayableSidebarButtonsSection headingLabel={t('Management')}>
          <PlayableSidebarButton href={`/manage/events/${event.id}`} IconComponent={LuWrench}>
            {t('Event Details')}
          </PlayableSidebarButton>

          {!event.legacyGame?.forumTopicId && can.createGameForumTopic ? (
            <GameCreateForumTopicButton game={event.legacyGame!} />
          ) : null}
        </PlayableSidebarButtonsSection>
      ) : null}
    </div>
  );
};
