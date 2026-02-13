import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { ReorderSiteAwardsMainRoot } from '@/features/reorder-site-awards/components/+root';
import { ReorderSiteAwardsSidebarRoot } from '@/features/reorder-site-awards/components/+sidebar';

const ReorderSiteAwards: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Reorder Site Awards')}
        description="Reorder Site Awards"
        noindex={true}
        nofollow={true}
      />

      <AppLayout.Main>
        <ReorderSiteAwardsMainRoot />
      </AppLayout.Main>

      <AppLayout.Sidebar>
        <ReorderSiteAwardsSidebarRoot />
      </AppLayout.Sidebar>
    </>
  );
};

ReorderSiteAwards.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default ReorderSiteAwards;
