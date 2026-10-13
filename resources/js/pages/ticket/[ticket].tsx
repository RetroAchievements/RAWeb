import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TicketShowRoot } from '@/features/tickets/components/+show';
import { TicketPropertiesPanel } from '@/features/tickets/components/TicketPropertiesPanel';
import { useTicketTypeLabels } from '@/features/tickets/hooks/useTicketTypeLabels';

const TicketShow: AppPage = () => {
  const { ticket, ticketableDescription } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const typeLabels = useTicketTypeLabels();

  return (
    <>
      <SEO
        title={t('Ticket {{ticketId}}: {{title}} ({{type}})', {
          ticketId: ticket.id,
          title: ticket.ticketableTitle,
          type: typeLabels[ticket.type],
        })}
        description={ticketableDescription}
        ogImage={ticket.ticketableBadgeUrl ?? ticket.game.badgeUrl}
      />

      <AppLayout.Main>
        <TicketShowRoot />
      </AppLayout.Main>

      <AppLayout.Sidebar>
        <TicketPropertiesPanel />
      </AppLayout.Sidebar>
    </>
  );
};

TicketShow.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default TicketShow;
