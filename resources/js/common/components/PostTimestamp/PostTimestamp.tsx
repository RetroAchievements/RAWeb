import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';

import { diffForHumans } from '@/utils/diffForHumans';

dayjs.extend(utc);

interface PostTimestampProps {
  postedAt: string;
  asAbsoluteDate: boolean;
}

export const PostTimestamp: FC<PostTimestampProps> = ({ postedAt, asAbsoluteDate }) => {
  if (asAbsoluteDate) {
    return dayjs.utc(postedAt).format('DD MMM YYYY, HH:mm');
  }

  return diffForHumans(postedAt);
};
