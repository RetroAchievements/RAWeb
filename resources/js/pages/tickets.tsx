import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import type { AppPage } from '@/common/models';
import { TicketIndexRoot } from '@/features/tickets/components/+index';
import { TicketPageLayout } from '@/features/tickets/components/TicketPageLayout';

const TicketIndex: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      {/* I'm not too fixated on this meta description - this page requires auth */}
      <SEO title={t('Tickets')} description="Browse all tickets on RetroAchievements" />

      <TicketIndexRoot />
    </>
  );
};

TicketIndex.layout = (page) => <TicketPageLayout currentView="all">{page}</TicketPageLayout>;

export default TicketIndex;
