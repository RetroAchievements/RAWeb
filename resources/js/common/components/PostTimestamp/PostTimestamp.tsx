import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { type FC } from 'react';

import { useServerRenderTime } from '@/common/hooks/useServerRenderTime';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { diffForHumans } from '@/utils/diffForHumans';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

dayjs.extend(utc);

interface PostTimestampProps {
  postedAt: string;
  asAbsoluteDate: boolean;
}

export const PostTimestamp: FC<PostTimestampProps> = ({ postedAt, asAbsoluteDate }) => {
  const { renderedAt } = useServerRenderTime();

  const date = dayjs.utc(postedAt);

  if (asAbsoluteDate) {
    return formatDate(date, 'MMM DD, YYYY, HH:mm');
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger className="cursor-default">
        <span>{diffForHumans(postedAt, renderedAt)}</span>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <p className="text-xs">{formatDate(date, 'lll')}</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
