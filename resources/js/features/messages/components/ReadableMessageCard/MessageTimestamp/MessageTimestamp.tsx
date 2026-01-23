import dayjs from 'dayjs';
import type { FC } from 'react';

import { useFormatDate } from '@/common/hooks/useFormatDate';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

interface MessageTimestampProps {
  message: App.Community.Data.Message;
}

export const MessageTimestamp: FC<MessageTimestampProps> = ({ message }) => {
  const { auth } = usePageProps();
  const { formatDate } = useFormatDate();
  const { diffForHumans } = useDiffForHumans();

  const { createdAt } = message;
  const monthAgo = dayjs().subtract(1, 'month');
  const isOlderThanMonth = dayjs(createdAt).isBefore(monthAgo);

  const prefersAbsoluteDates = auth?.user.preferences.prefersAbsoluteDates;

  // Get absolute date format based on user preference.
  const absoluteDate = formatDate(createdAt, prefersAbsoluteDates ? 'MMM DD, YYYY, HH:mm' : 'LLL');

  // Render absolute date if user prefers it or if the message is old enough.
  const shouldShowAbsoluteDate = prefersAbsoluteDates || isOlderThanMonth;

  return shouldShowAbsoluteDate ? (
    <p className="smalldate">{absoluteDate}</p>
  ) : (
    <p className="smalldate cursor-help" title={absoluteDate}>
      {diffForHumans(createdAt)}
    </p>
  );
};
