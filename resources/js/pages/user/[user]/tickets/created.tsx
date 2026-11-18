import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TicketIndexRoot } from '@/features/tickets/components/+index';

const CreatedTickets: AppPage = () => {
  const { user } = usePageProps<App.Platform.Data.TicketListPageProps>();
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Tickets Created - {{title}}', { title: user!.displayName })}
        description={`Tickets created by ${user!.displayName}`}
      />

      <AppLayout.Main>
        <TicketIndexRoot />
      </AppLayout.Main>
    </>
  );
};

CreatedTickets.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default CreatedTickets;
