import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useTicketResolutionLabels } from '../../hooks/useTicketResolutionLabels';
import { useTicketStateLabels } from '../../hooks/useTicketStateLabels';
import { buildAchievementTicketListHref } from '../../utils/buildAchievementTicketListHref';
import { TicketEntriesTable } from '../TicketEntriesTable';

const MAX_VISIBLE_RELATED_TICKETS = 8;

export const RelatedTicketsPanel: FC = () => {
  const { relatedTickets, ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const resolutionLabels = useTicketResolutionLabels();
  const stateLabels = useTicketStateLabels();

  const seeAllTicketsHref =
    relatedTickets.length > MAX_VISIBLE_RELATED_TICKETS
      ? buildAchievementTicketListHref(ticket)
      : null;

  const allTicketsCount = relatedTickets.length + 1; // also includes the ticket being viewed

  return (
    <section>
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Related Tickets')}</h2>

      <TicketEntriesTable
        entries={relatedTickets.slice(0, MAX_VISIBLE_RELATED_TICKETS)}
        labelHeading={t('Status')}
        getLabel={(relatedEntry) =>
          relatedEntry.resolution
            ? resolutionLabels[relatedEntry.resolution]
            : stateLabels[relatedEntry.state]
        }
        footer={
          seeAllTicketsHref ? (
            <InertiaLink href={seeAllTicketsHref} className="text-xs" prefetch="desktop-hover-only">
              {t('View all {{val, number}} tickets', { val: allTicketsCount })}
            </InertiaLink>
          ) : null
        }
      />
    </section>
  );
};
