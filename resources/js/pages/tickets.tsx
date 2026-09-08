import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TicketIndexRoot } from '@/features/tickets/components/+index';

const TicketIndex: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      {/* I'm not too fixated on this meta description - this page requires auth */}
      <SEO title={t('Ticket Manager')} description="Browse all tickets on RetroAchievements" />

      <AppLayout.Main>
        <TicketIndexRoot />
      </AppLayout.Main>
    </>
  );
};

TicketIndex.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default TicketIndex;
