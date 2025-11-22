import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuFlag } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { MessageTimestamp } from './MessageTimestamp';

interface ReadableMessageCardProps {
  message: App.Community.Data.Message;

  isHighlighted?: boolean;
}

export const ReadableMessageCard: FC<ReadableMessageCardProps> = ({
  message,
  isHighlighted = false,
}) => {
  const { auth, can, senderUserDisplayName } =
    usePageProps<App.Community.Data.MessageThreadShowPageProps>();
  const { t } = useTranslation();

  const canReport =
    can?.createModerationReports &&
    message.author?.displayName !== auth?.user.displayName &&
    senderUserDisplayName === auth?.user.displayName;

  return (
    <div
      id={`${message.id}`}
      data-testid={`message-${message.id}`}
      className={cn(
        'scroll-mt-14 rounded bg-embed px-2.5 py-1.5',
        isHighlighted ? 'outline outline-2' : null,
      )}
    >
      <div className="flex w-full flex-col justify-between gap-1 sm:flex-row sm:items-center sm:gap-2">
        <div className="flex items-center gap-2">
          <UserAvatar {...message.author!} size={24} />
          <MessageTimestamp message={message} />
        </div>

        <div className="flex items-center gap-2">
          {canReport ? (
            <a
              href={route('message-thread.create', {
                to: 'RAdmin',
                subject: `Report: Direct Message by ${message.author?.displayName}`,
                rType: 'DirectMessage',
                rId: message.id,
              })}
              className={baseButtonVariants({
                size: 'sm',
                className: 'max-h-[22px] gap-1 !p-1 !text-2xs',
              })}
            >
              <LuFlag className="size-3" />
              {t('Report')}
            </a>
          ) : null}

          {message.sentBy ? (
            <Trans
              i18nKey="Sent by <1>{{username}}</1>"
              values={{ username: message.sentBy.displayName }}
              components={{
                1: <UserAvatar {...message.sentBy} size={24} />,
              }}
            />
          ) : null}
        </div>
      </div>

      <hr className="my-2 w-full border-embed-highlight" />

      <div style={{ wordBreak: 'break-word' }}>
        <ShortcodeRenderer body={message.body} />
      </div>
    </div>
  );
};
