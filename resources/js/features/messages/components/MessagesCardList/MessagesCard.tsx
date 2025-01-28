import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

interface MessagesCardProps {
  messageThread: App.Community.Data.MessageThread;
}

export const MessagesCard: FC<MessagesCardProps> = ({ messageThread }) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const messages = messageThread.messages as App.Community.Data.Message[];
  const lastMessage = messages[messages.length - 1];

  const otherParticipant =
    (messageThread.participants?.find(
      (p) => p.displayName !== auth?.user.displayName,
    ) as App.Data.User) ?? messageThread.participants?.[0];

  return (
    <div className="embedded">
      <div className="flex flex-col gap-2">
        <div className="flex items-center justify-between">
          <UserAvatar {...otherParticipant} size={16} />

          <span className="text-2xs text-neutral-400 light:text-neutral-700">
            <DiffTimestamp
              asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
              at={lastMessage.createdAt}
            />
          </span>
        </div>

        <div>
          <a
            href={route('message-thread.show', messageThread.id)}
            className={cn(messageThread.isUnread ? 'font-bold' : null)}
          >
            {messageThread.title}
          </a>

          <p>
            {t('messageCount', { count: messageThread.numMessages })}{' '}
            {messageThread.isUnread ? t('(unread)') : null}
          </p>
        </div>
      </div>
    </div>
  );
};
