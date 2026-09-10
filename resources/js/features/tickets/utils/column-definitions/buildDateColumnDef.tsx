import type { FC } from 'react';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { cn } from '@/common/utils/cn';
import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition } from '../../models';
import { ticketListCellClassNames } from './ticketListCellClassNames';

interface BuildDateColumnDefProps {
  id: 'age' | 'resolvedAt';
  t_label: TranslatedString;
}

export function buildDateColumnDef({
  id,
  t_label,
}: BuildDateColumnDefProps): TicketListColumnDefinition {
  return {
    id,
    meta: {
      t_label,
      align: 'right',
      responsiveClassName: 'w-[6em] flex-none text-right tabular-nums',
    },

    cell: ({ row }) => <DateCell date={row.original[id === 'age' ? 'createdAt' : 'resolvedAt']} />,
  };
}

interface DateCellProps {
  date: string | null;
}

// TODO extract to common when something like this is needed later
const DateCell: FC<DateCellProps> = ({ date }) => {
  const { formatDate } = useFormatDate();
  const { diffForHumans } = useDiffForHumans();

  if (!date) {
    return <span className={ticketListCellClassNames.dimText}>{'-'}</span>;
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <span
          className={cn(ticketListCellClassNames.dimText, 'relative z-10 block truncate')}
          suppressHydrationWarning={true}
        >
          {diffForHumans(date, { style: 'narrow' })}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent>{formatDate(date, 'lll')}</BaseTooltipContent>
    </BaseTooltip>
  );
};
