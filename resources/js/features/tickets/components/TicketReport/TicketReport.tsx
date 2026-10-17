import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { FormatNewlines } from '@/common/components/FormatNewlines';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

interface TicketReportProps {
  reportBody: string;
  ticket: App.Platform.Data.TicketListEntry;
}

export const TicketReport: FC<TicketReportProps> = ({ reportBody, ticket }) => {
  const { auth } = usePageProps();
  const { t } = useTranslation();

  return (
    <div
      role="article"
      aria-label={t('Report')}
      className={cn(
        'flex w-full items-start gap-4 rounded-lg border border-neutral-700 bg-embed p-2',
        'light:border-neutral-200 light:bg-white',
      )}
    >
      <div className="mt-1">
        {ticket.reporter ? (
          <UserAvatar {...ticket.reporter} showLabel={false} />
        ) : (
          <div className="size-8" />
        )}
      </div>

      <div className="w-full">
        <div className="flex items-center gap-2">
          {ticket.reporter ? (
            <UserAvatar {...ticket.reporter} showImage={false} />
          ) : (
            <span className="text-neutral-500">{t('Deleted user')}</span>
          )}

          <span className="smalldate">
            <DiffTimestamp
              asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
              at={ticket.createdAt}
            />
          </span>
        </div>

        <p style={{ wordBreak: 'break-word' }}>
          <FormatNewlines>{reportBody}</FormatNewlines>
        </p>
      </div>
    </div>
  );
};
