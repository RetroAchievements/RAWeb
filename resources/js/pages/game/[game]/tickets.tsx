import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TicketIndexRoot } from '@/features/tickets/components/+index';

const GameTickets: AppPage = () => {
  const { game } = usePageProps<App.Platform.Data.TicketListPageProps>();
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Tickets - {{title}}', { title: game!.title })}
        description={`Open tickets for ${game!.title}`}
      />

      <AppLayout.Main>
        <TicketIndexRoot />
      </AppLayout.Main>
    </>
  );
};

GameTickets.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameTickets;
