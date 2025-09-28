import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameSetRequests } from '@/features/games/components/GameSetRequests';

const SetRequestorsPage: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('List of Set Requests')} description="A list of set requests for this game." />

      <div className="container">
        <AppLayout.Main className="min-h-[400px]">
          <GameSetRequests />
        </AppLayout.Main>
      </div>
    </>
  );
};

SetRequestorsPage.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default SetRequestorsPage;
