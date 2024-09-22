import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';

import { formatDate } from '@/common/utils/l10n/formatDate';
import { diffForHumans } from '@/utils/diffForHumans';

dayjs.extend(utc);

interface PostTimestampProps {
  postedAt: string;
  asAbsoluteDate: boolean;
}

export const PostTimestamp: FC<PostTimestampProps> = ({ postedAt, asAbsoluteDate }) => {
  if (asAbsoluteDate) {
    return formatDate(dayjs.utc(postedAt), 'MMM DD, YYYY, HH:mm');
  }

  return diffForHumans(postedAt);
};
