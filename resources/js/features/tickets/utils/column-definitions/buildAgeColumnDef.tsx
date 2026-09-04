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

interface BuildAgeColumnDefProps {
  t_label: TranslatedString;
}

export function buildAgeColumnDef({ t_label }: BuildAgeColumnDefProps): TicketListColumnDefinition {
  return {
    id: 'age',
    meta: {
      t_label,
      align: 'right',
      responsiveClassName: 'w-[6em] flex-none text-right tabular-nums',
    },

    cell: ({ row }) => <AgeCell createdAt={row.original.createdAt} />,
  };
}

interface AgeCellProps {
  createdAt: string;
}

const AgeCell: FC<AgeCellProps> = ({ createdAt }) => {
  const { formatDate } = useFormatDate();
  const { diffForHumans } = useDiffForHumans();

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <span
          className={cn(ticketListCellClassNames.dimText, 'block truncate')}
          suppressHydrationWarning={true}
        >
          {diffForHumans(createdAt, { style: 'narrow' })}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent>{formatDate(createdAt, 'MMM DD, YYYY, HH:mm')}</BaseTooltipContent>
    </BaseTooltip>
  );
};
