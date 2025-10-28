import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { RedirectRoot } from '@/features/redirect/components/+root';

const Redirect: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Redirect')} description="Redirect page" />

      <AppLayout.Main>
        <RedirectRoot />
      </AppLayout.Main>
    </>
  );
};

Redirect.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Redirect;
