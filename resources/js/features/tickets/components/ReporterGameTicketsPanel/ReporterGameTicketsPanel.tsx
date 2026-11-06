import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { TicketEntriesTable } from '../TicketEntriesTable';

const DEFAULT_VISIBLE_TICKET_COUNT = 5;

export const ReporterGameTicketsPanel: FC = () => {
  const { reporterGameTickets, ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const [isExpanded, setIsExpanded] = useState(false);

  const collapsedOverflowCount = reporterGameTickets.length - DEFAULT_VISIBLE_TICKET_COUNT;

  return (
    <section>
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('From the Same Reporter')}</h2>

      <TicketEntriesTable
        entries={reporterGameTickets}
        collapsedRowCount={DEFAULT_VISIBLE_TICKET_COUNT}
        isExpanded={isExpanded}
        labelHeading={t('Title')}
        getLabel={(listEntry) => listEntry.ticketableTitle}
        timingFrom={ticket.createdAt}
        footer={
          collapsedOverflowCount > 0 ? (
            <BaseButton
              size="sm"
              className="w-full justify-center border-none"
              aria-expanded={isExpanded}
              onClick={() => setIsExpanded((wasExpanded) => !wasExpanded)}
            >
              {t('See {{val, number}} more', { val: collapsedOverflowCount })}

              <LuChevronDown
                className={cn(
                  'ml-1 size-4 transition-transform duration-300',
                  isExpanded ? 'rotate-180' : 'rotate-0',
                )}
              />
            </BaseButton>
          ) : null
        }
      />
    </section>
  );
};
