import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TicketNoLongerAProblemRoot } from '@/features/tickets/components/+no-longer-a-problem';

const TicketNoLongerAProblem: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Close Your Ticket')}
        description="Close a ticket that is no longer a problem"
      />

      <AppLayout.Main>
        <TicketNoLongerAProblemRoot />
      </AppLayout.Main>
    </>
  );
};

TicketNoLongerAProblem.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default TicketNoLongerAProblem;
