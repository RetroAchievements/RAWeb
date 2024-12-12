import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { WantToPlayGamesMainRoot } from '@/features/game-list/components/WantToPlayGamesMainRoot';

const WantToPlayGames: AppPage = () => {
  const { t } = useTranslation();

  const metaDescription = 'A list of your Want to Play Games';

  return (
    <>
      <Head title={t('Want to Play Games')}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />
      </Head>

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
