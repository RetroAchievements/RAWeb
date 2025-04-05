import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { type FC } from 'react';

import { useServerRenderTime } from '@/common/hooks/useServerRenderTime';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

dayjs.extend(utc);

interface DiffTimestampProps {
  /** ISO8601 */
  at: string;

  asAbsoluteDate?: boolean;
  className?: string;
  enableTooltip?: boolean;
}

export const DiffTimestamp: FC<DiffTimestampProps> = ({
  at,
  className,
  asAbsoluteDate = false,
  enableTooltip = true,
}) => {
  const { renderedAt } = useServerRenderTime();

  const { diffForHumans } = useDiffForHumans();

  const date = dayjs.utc(at);

  if (asAbsoluteDate) {
    return formatDate(date, 'MMM DD, YYYY, HH:mm');
  }

  if (!enableTooltip) {
    return (
      <span className={className} suppressHydrationWarning={true}>
        {diffForHumans(at, renderedAt)}
      </span>
    );
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger className="cursor-default">
        <span className={className} suppressHydrationWarning={true}>
          {diffForHumans(at, renderedAt)}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <p className="text-xs">{formatDate(date, 'lll')}</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
