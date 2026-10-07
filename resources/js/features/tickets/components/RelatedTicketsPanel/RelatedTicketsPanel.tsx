import dayjs from 'dayjs';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';
import { getIsTicketStateOpen } from '@/common/utils/getIsTicketStateOpen';

import { useTicketResolutionLabels } from '../../hooks/useTicketResolutionLabels';
import { buildAchievementTicketListHref } from '../../utils/buildAchievementTicketListHref';
import { getTicketStateLabel } from '../../utils/getTicketStateLabel';
import { TicketStateGlyph } from '../TicketStateGlyph';

const MAX_VISIBLE_RELATED_TICKETS = 8;

export const RelatedTicketsPanel: FC = () => {
  const { relatedTickets, ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const resolutionLabels = useTicketResolutionLabels();

  // Open tickets should sort ahead of everything else.
  const prioritizedTickets = [...relatedTickets].sort((a, b) => {
    const openBeforeResolved =
      Number(getIsTicketStateOpen(b.state)) - Number(getIsTicketStateOpen(a.state));

    if (openBeforeResolved !== 0) {
      return openBeforeResolved;
    }

    return dayjs(b.createdAt).diff(a.createdAt);
  });
  const listedTickets = prioritizedTickets.slice(0, MAX_VISIBLE_RELATED_TICKETS);

  const seeAllTicketsHref =
    relatedTickets.length > MAX_VISIBLE_RELATED_TICKETS
      ? buildAchievementTicketListHref(ticket)
      : null;

  return (
    <section>
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Related Tickets')}</h2>

      <div className="flex flex-col gap-2 rounded-lg bg-embed p-2 light:border light:border-neutral-200 light:bg-white">
        <BaseTable className="table-highlight overflow-hidden rounded-lg outline-1 outline-neutral-800 light:outline-white">
          <BaseTableHeader className="border-neutral-800">
            <BaseTableRow className="do-not-highlight text-menu-link">
              <BaseTableCell>{t('Ticket')}</BaseTableCell>
              <BaseTableCell>{t('Status')}</BaseTableCell>
              <BaseTableCell>{t('Created')}</BaseTableCell>
            </BaseTableRow>
          </BaseTableHeader>

          <BaseTableBody>
            {listedTickets.map((listedTicket) => {
              const statusLabel = listedTicket.resolution
                ? resolutionLabels[listedTicket.resolution]
                : getTicketStateLabel(listedTicket.state, t);

              return (
                <BaseTableRow key={listedTicket.id} className="last:rounded-b-lg [&>td]:py-1.5">
                  <BaseTableCell className="whitespace-nowrap">
                    <span className="flex items-center gap-2">
                      <TicketStateGlyph state={listedTicket.state} />

                      <InertiaLink
                        href={route('ticket2.show', { ticket: listedTicket.id })}
                        prefetch="desktop-hover-only"
                      >
                        {`#${listedTicket.id}`}
                      </InertiaLink>
                    </span>
                  </BaseTableCell>

                  <BaseTableCell className="w-full max-w-0 truncate" title={statusLabel}>
                    {statusLabel}
                  </BaseTableCell>

                  <BaseTableCell className="smalldate whitespace-nowrap">
                    <DiffTimestamp at={listedTicket.createdAt} style="narrow" />
                  </BaseTableCell>
                </BaseTableRow>
              );
            })}
          </BaseTableBody>
        </BaseTable>

        {seeAllTicketsHref ? (
          <div className="flex w-full justify-end">
            <InertiaLink
              href={seeAllTicketsHref}
              className="text-2xs"
              prefetch="desktop-hover-only"
            >
              {t('See more')}
            </InertiaLink>
          </div>
        ) : null}
      </div>
    </section>
  );
};
