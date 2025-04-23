import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';

import { OfficialForumTopicButton } from '../OfficialForumTopicButton';

interface EventSidebarFullWidthButtonsProps {
  event: App.Platform.Data.Event;
}

export const EventSidebarFullWidthButtons: FC<EventSidebarFullWidthButtonsProps> = ({ event }) => {
  const { can } = usePageProps<App.Platform.Data.EventShowPageProps>();

  const { t } = useTranslation();

  return (
    <div className="flex flex-col gap-2">
      <OfficialForumTopicButton game={event.legacyGame!} />

      {can.manageEvents ? (
        <a
          href={`/manage/events/${event.id}`}
          className={baseButtonVariants({ size: 'sm', className: 'flex max-h-[28px] gap-1.5' })}
          target="_blank"
        >
          <LuWrench className="size-4 text-neutral-300 light:text-neutral-700" />
          <span>{t('Manage')}</span>
        </a>
      ) : null}
    </div>
  );
};
