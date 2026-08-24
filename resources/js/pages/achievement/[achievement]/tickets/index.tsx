import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TicketIndexRoot } from '@/features/tickets/components/+index';

const AchievementTickets: AppPage = () => {
  const { achievement } = usePageProps<App.Platform.Data.TicketListPageProps>();
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Tickets - {{title}}', { title: achievement!.title })}
        description={`Open tickets for ${achievement!.title}`}
      />

      <AppLayout.Main>
        <TicketIndexRoot />
      </AppLayout.Main>
    </>
  );
};

AchievementTickets.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AchievementTickets;
