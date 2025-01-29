import type { FC } from 'react';

import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface ReadableMessageCardProps {
  message: App.Community.Data.Message;
}

export const ReadableMessageCard: FC<ReadableMessageCardProps> = ({ message }) => {
  const { auth } = usePageProps();

  return (
    <div className="rounded bg-embed px-2.5 py-1.5">
      <div className="flex items-center gap-2">
        <UserAvatar {...message.author!} size={24} />

        <p className="smalldate">
          {formatDate(
            message.createdAt,
            auth?.user.preferences.prefersAbsoluteDates ? 'MMM DD, YYYY, HH:mm' : 'LLL',
          )}
        </p>
      </div>

      <hr className="my-2 w-full border-embed-highlight" />

      <div style={{ wordBreak: 'break-word' }}>
        <ShortcodeRenderer body={message.body} />
      </div>
    </div>
  );
};
