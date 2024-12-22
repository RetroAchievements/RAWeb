import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { WantToPlayGamesMainRoot } from '@/features/game-list/components/WantToPlayGamesMainRoot';

const WantToPlayGames: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Want to Play Games')} description="A list of your Want to Play Games" />

      <div className="container">
        <AppLayout.Main>
          <WantToPlayGamesMainRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

WantToPlayGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default WantToPlayGames;
