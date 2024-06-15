import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';

dayjs.extend(utc);
dayjs.extend(relativeTime);

interface PostTimestampProps {
  postedAt: string;
  asAbsoluteDate: boolean;
}

export const PostTimestamp: FC<PostTimestampProps> = ({ postedAt, asAbsoluteDate }) => {
  if (asAbsoluteDate) {
    return dayjs.utc(postedAt).format('DD MMM YYYY, HH:mm');
  }

  return dayjs.utc(postedAt).fromNow();
};
