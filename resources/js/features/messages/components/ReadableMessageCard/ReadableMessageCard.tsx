import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';
import { UserAvatar } from '@/common/components/UserAvatar';

import { MessageTimestamp } from './MessageTimestamp';

interface ReadableMessageCardProps {
  message: App.Community.Data.Message;
}

export const ReadableMessageCard: FC<ReadableMessageCardProps> = ({ message }) => {
  return (
    <div className="rounded bg-embed px-2.5 py-1.5">
      <div className="flex w-full flex-col justify-between gap-1 sm:flex-row sm:items-center sm:gap-2">
        <div className="flex items-center gap-2">
          <UserAvatar {...message.author!} size={24} />
          <MessageTimestamp message={message} />
        </div>

        {message.sentBy ? (
          <div className="flex items-center gap-2">
            <Trans
              i18nKey="Sent by <1>{{username}}</1>"
              values={{ username: message.sentBy.displayName }}
              components={{
                1: <UserAvatar {...message.sentBy} size={24} />,
              }}
            />
          </div>
        ) : null}
      </div>

      <hr className="my-2 w-full border-embed-highlight" />

      <div style={{ wordBreak: 'break-word' }}>
        <ShortcodeRenderer body={message.body} />
      </div>
    </div>
  );
};
