import type { FC, ReactNode } from 'react';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipPortal,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatDate } from '@/common/hooks/useFormatDate';

interface DateTooltipProps {
  date: string;

  children?: ReactNode;
  className?: string;
}

export const TicketDateTooltip: FC<DateTooltipProps> = ({ children, className, date }) => {
  const { formatDate } = useFormatDate();

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <span className={className} suppressHydrationWarning={true}>
          {children}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipPortal>
        <BaseTooltipContent>{formatDate(date, 'lll')}</BaseTooltipContent>
      </BaseTooltipPortal>
    </BaseTooltip>
  );
};
