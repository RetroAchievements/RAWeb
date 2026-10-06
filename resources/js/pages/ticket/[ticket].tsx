import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

const TicketShow: AppPage = () => {
  // Do a pick for `ticketData`. We're just going to dump it to the screen for now.
  const {
    auth: _auth,
    config: _config,
    csrfToken: _csrfToken,
    errors: _errors,
    flash: _flash,
    metaKey: _metaKey,
    ziggy: _ziggy,
    ...ticketData
  } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Ticket #{{ticketId}}', { ticketId: ticketData.ticket.id })}
        description={ticketData.ticketableDescription}
        ogImage={ticketData.ticket.ticketableBadgeUrl ?? ticketData.ticket.game.badgeUrl}
      />

      <AppLayout.Main>
        <pre className="overflow-x-auto text-2xs">{JSON.stringify(ticketData, null, 2)}</pre>
      </AppLayout.Main>
    </>
  );
};

TicketShow.layout = (pageContent) => <AppLayout withSidebar={false}>{pageContent}</AppLayout>;

export default TicketShow;
