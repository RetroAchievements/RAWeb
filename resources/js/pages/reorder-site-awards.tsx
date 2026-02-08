import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import ReorderSiteAwardsMainRoot from '@/features/reorder-site-awards/components/+root/ReorderSiteAwardsMainRoot';

const ReorderSiteAwards: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Redirect')} description="Redirect page" noindex={true} nofollow={true} />

      <AppLayout.Main>
        <ReorderSiteAwardsMainRoot />
      </AppLayout.Main>
    </>
  );
};

ReorderSiteAwards.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default ReorderSiteAwards;
