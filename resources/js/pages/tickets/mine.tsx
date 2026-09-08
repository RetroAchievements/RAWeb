import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TicketInboxMainRoot } from '@/features/tickets/components/+mine';

const TicketInbox: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Tickets')} description="Tickets that need your attention" />

      <AppLayout.Main>
        <TicketInboxMainRoot />
      </AppLayout.Main>
    </>
  );
};

TicketInbox.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default TicketInbox;
