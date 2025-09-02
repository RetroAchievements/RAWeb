import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UnsubscribeShowMainRoot } from '@/features/unsubscribe/components/+show';

const UnsubscribeShow: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Unsubscribe')} description="Unsubscribe from an email subscription" />

      <AppLayout.Main>
        <UnsubscribeShowMainRoot />
      </AppLayout.Main>
    </>
  );
};

UnsubscribeShow.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UnsubscribeShow;
