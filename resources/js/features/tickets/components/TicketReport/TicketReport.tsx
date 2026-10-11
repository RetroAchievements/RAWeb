import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { FormatNewlines } from '@/common/components/FormatNewlines';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { cn } from '@/common/utils/cn';

import { TicketUserValue } from '../TicketUserValue';

interface TicketReportProps {
  reportBody: string;
  ticket: App.Platform.Data.TicketListEntry;
}

export const TicketReport: FC<TicketReportProps> = ({ reportBody, ticket }) => {
  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  return (
    <section className="flex flex-col gap-2">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Report')}</h2>

      <div
        className={cn(
          'flex flex-col gap-2 rounded-lg border border-neutral-700 bg-embed p-3',
          'light:border-neutral-200 light:bg-white',
        )}
      >
        <div className="flex items-center gap-2">
          <TicketUserValue user={ticket.reporter} />
          <span className="smalldate">{formatDate(ticket.createdAt, 'lll')}</span>
        </div>

        <p style={{ wordBreak: 'break-word' }}>
          <FormatNewlines>{reportBody}</FormatNewlines>
        </p>
      </div>
    </section>
  );
};
