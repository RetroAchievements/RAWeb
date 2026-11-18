import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import type { AppPage } from '@/common/models';
import { TicketInboxMainRoot } from '@/features/tickets/components/+mine';
import { TicketPageLayout } from '@/features/tickets/components/TicketPageLayout';

const TicketInbox: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Tickets')} description="Tickets that need your attention" />

      <TicketInboxMainRoot />
    </>
  );
};

TicketInbox.layout = (page) => <TicketPageLayout currentView="mine">{page}</TicketPageLayout>;

export default TicketInbox;
