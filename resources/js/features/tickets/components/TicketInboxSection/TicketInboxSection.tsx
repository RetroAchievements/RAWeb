import type { VisibilityState } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

import { useTicketListColumnDefinitions } from '../../hooks/useTicketListColumnDefinitions';
import type { TicketListColumnId } from '../../models';
import { TICKET_LIST_COLUMN_IDS } from '../../utils/ticketListColumnIds';
import { TicketListTable } from '../TicketListTable';

interface TicketInboxSectionProps {
  counterpartyColumnId: Extract<TicketListColumnId, 'developer' | 'reporter'>;
  section: App.Platform.Data.TicketInboxSection;
  t_heading: TranslatedString;

  t_emptyMessage?: TranslatedString;
  viewAllHref?: string;
}

export const TicketInboxSection: FC<TicketInboxSectionProps> = ({
  counterpartyColumnId,
  section,
  t_heading,
  t_emptyMessage,
  viewAllHref,
}) => {
  const { sectionLimit } = usePageProps<App.Platform.Data.TicketInboxPageProps>();
  const { t } = useTranslation();
  const { formatNumber } = useFormatNumber();

  const columnDefinitions = useTicketListColumnDefinitions();

  if (!section.count && !t_emptyMessage) {
    return null;
  }

  const visibleColumnIds = new Set<string>([
    'id',
    'ticketable',
    'game',
    counterpartyColumnId,
    'age',
  ]);
  const columnVisibility: VisibilityState = Object.fromEntries(
    TICKET_LIST_COLUMN_IDS.map((columnId) => [columnId, visibleColumnIds.has(columnId)]),
  );

  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-baseline justify-between gap-2">
        <h2 className="text-h4 border-b-0">
          {t_heading}

          {section.count ? (
            <span className="ml-2 text-neutral-400 light:text-neutral-600">
              {formatNumber(section.count)}
            </span>
          ) : null}
        </h2>

        {viewAllHref && section.count > sectionLimit ? (
          <a href={viewAllHref} className="text-link">
            {t('View all')}
          </a>
        ) : null}
      </div>

      {section.count ? (
        <TicketListTable
          columnDefinitions={columnDefinitions}
          columnVisibility={columnVisibility}
          paginatedTickets={{
            items: section.tickets,
            currentPage: 1,
            lastPage: 1,
            total: section.count,
          }}
        />
      ) : (
        <p className="text-neutral-300 light:text-neutral-700">{t_emptyMessage}</p>
      )}
    </div>
  );
};
