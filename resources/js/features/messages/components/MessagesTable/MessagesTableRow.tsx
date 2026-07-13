import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { InertiaLink } from '@/common/components/InertiaLink';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { formatNumber } from '@/common/utils/l10n/formatNumber';
import { getOtherParticipant } from '@/features/messages/utils/getOtherParticipant';

interface MessagesTableRowProps {
  messageThread: App.Community.Data.MessageThread;
}

export const MessagesTableRow: FC<MessagesTableRowProps> = ({ messageThread }) => {
  const { auth, senderUserDisplayName } =
    usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  const { t } = useTranslation();

  const otherParticipant = getOtherParticipant(messageThread, senderUserDisplayName);

  return (
    <BaseTableRow className={cn(messageThread.isUnread ? 'font-bold' : null)}>
      <BaseTableCell>
        <InertiaLink href={route('message-thread.show', messageThread.id)}>
          {messageThread.title}
        </InertiaLink>
      </BaseTableCell>

      <BaseTableCell>
        {otherParticipant ? <UserAvatar {...otherParticipant} size={24} /> : null}
      </BaseTableCell>

      <BaseTableCell className="text-right">
        {formatNumber(messageThread.numMessages)} {messageThread.isUnread ? t('(unread)') : null}
      </BaseTableCell>

      <BaseTableCell className="text-right">
        <DiffTimestamp
          asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
          at={messageThread.lastMessage!.createdAt}
          className="text-2xs text-neutral-400 light:text-neutral-800"
        />
      </BaseTableCell>
    </BaseTableRow>
  );
};
