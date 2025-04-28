import type { FC } from 'react';

import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';
import { UserAvatar } from '@/common/components/UserAvatar';

import { MessageTimestamp } from './MessageTimestamp';

interface ReadableMessageCardProps {
  message: App.Community.Data.Message;
}

export const ReadableMessageCard: FC<ReadableMessageCardProps> = ({ message }) => {
  return (
    <div className="rounded bg-embed px-2.5 py-1.5">
      <div className="flex items-center gap-2">
        <UserAvatar {...message.author!} size={24} />
        <MessageTimestamp message={message} />
      </div>

      <hr className="my-2 w-full border-embed-highlight" />

      <div style={{ wordBreak: 'break-word' }}>
        <ShortcodeRenderer body={message.body} />
      </div>
    </div>
  );
};
