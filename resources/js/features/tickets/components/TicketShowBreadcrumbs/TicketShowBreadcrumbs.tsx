import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';
import { GameTitle } from '@/common/components/GameTitle';
import { InertiaLink } from '@/common/components/InertiaLink';

interface TicketShowBreadcrumbsProps {
  ticket: App.Platform.Data.TicketListEntry;
}

export const TicketShowBreadcrumbs: FC<TicketShowBreadcrumbsProps> = ({ ticket }) => {
  const { t } = useTranslation();

  const { game } = ticket;
  const t_currentTicketCrumb = t('Ticket #{{ticketId}}', { ticketId: ticket.id });

  return (
    <div className="navpath hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem>
            <BaseBreadcrumbLink asChild>
              <InertiaLink href={route('tickets.index')} prefetch="desktop-hover-only">
                {t('Tickets')}
              </InertiaLink>
            </BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem aria-label={game.title}>
            <BaseBreadcrumbLink asChild>
              <InertiaLink
                href={route('game.tickets', { game: game.id })}
                prefetch="desktop-hover-only"
              >
                <GameTitle title={game.title} />
              </InertiaLink>
            </BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          <BaseBreadcrumbSeparator />

          {ticket.ticketableType === 'achievement' ? (
            <>
              <BaseBreadcrumbItem>
                <BaseBreadcrumbLink asChild>
                  <InertiaLink
                    href={route('achievement.tickets', {
                      achievement: ticket.ticketableId,
                      filter: { status: 'all' },
                    })}
                    prefetch="desktop-hover-only"
                  >
                    {ticket.ticketableTitle}
                  </InertiaLink>
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>

              <BaseBreadcrumbSeparator />
            </>
          ) : null}

          <BaseBreadcrumbItem aria-label={t_currentTicketCrumb}>
            <BaseBreadcrumbPage>{t_currentTicketCrumb}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
