import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

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
  style?: Intl.RelativeTimeFormatStyle;
}

export const DiffTimestamp: FC<DiffTimestampProps> = ({
  at,
  className,
  style,
  asAbsoluteDate = false,
  enableTooltip = true,
}) => {
  const { i18n } = useTranslation();

  const { renderedAt } = useServerRenderTime();
  const { diffForHumans } = useDiffForHumans();

  const date = dayjs.utc(at);

  if (asAbsoluteDate) {
    return formatDate(date, 'MMM DD, YYYY, HH:mm', i18n.language);
  }

  if (!enableTooltip) {
    return (
      <span className={className} suppressHydrationWarning={true}>
        {diffForHumans(at, { style, from: renderedAt })}
      </span>
    );
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger className="cursor-default">
        <span className={className} suppressHydrationWarning={true}>
          {diffForHumans(at, { style, from: renderedAt })}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="py-2.5">
        <p className="text-xs">{formatDate(date, 'lll', i18n.language)}</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
