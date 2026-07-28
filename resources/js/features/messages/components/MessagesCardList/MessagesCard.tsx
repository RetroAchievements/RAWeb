import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { InertiaLink } from '@/common/components/InertiaLink';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { getOtherParticipant } from '@/features/messages/utils/getOtherParticipant';

interface MessagesCardProps {
  messageThread: App.Community.Data.MessageThread;
}

export const MessagesCard: FC<MessagesCardProps> = ({ messageThread }) => {
  const { auth, senderUserDisplayName } =
    usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  const { t } = useTranslation();

  const otherParticipant = getOtherParticipant(messageThread, senderUserDisplayName);

  return (
    <div className="embedded">
      <div className="flex flex-col gap-2">
        <div className="flex items-center justify-between">
          {otherParticipant ? <UserAvatar {...otherParticipant} size={16} /> : <span />}

          <span className="text-2xs text-neutral-400 light:text-neutral-700">
            <DiffTimestamp
              asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
              at={messageThread.lastMessage!.createdAt}
            />
          </span>
        </div>

        <div>
          <InertiaLink
            href={route('message-thread.show', messageThread.id)}
            className={cn(messageThread.isUnread ? 'font-bold' : null)}
          >
            {messageThread.title}
          </InertiaLink>

          <p>
            {t('messageCount', { count: messageThread.numMessages })}{' '}
            {messageThread.isUnread ? t('(unread)') : null}
          </p>
        </div>
      </div>
    </div>
  );
};
