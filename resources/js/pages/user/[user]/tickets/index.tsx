import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TicketIndexRoot } from '@/features/tickets/components/+index';

const AssignedTickets: AppPage = () => {
  const { user } = usePageProps<App.Platform.Data.TicketListPageProps>();
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Tickets - {{title}}', { title: user!.displayName })}
        description={`Open tickets assigned to ${user!.displayName}`}
      />

      <AppLayout.Main>
        <TicketIndexRoot />
      </AppLayout.Main>
    </>
  );
};

AssignedTickets.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AssignedTickets;
