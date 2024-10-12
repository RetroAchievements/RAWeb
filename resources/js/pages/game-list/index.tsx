import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AllGamesMainRoot } from '@/features/game-list/components/AllGamesMainRoot';

const AllGames: AppPage = () => {
  const { t } = useLaravelReactI18n();

  return (
    <>
      <Head title={t('All Games')}>
        <meta name="description" content="TODO" />
      </Head>

      <div className="container">
        <AppLayout.Main>
          <AllGamesMainRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

AllGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AllGames;
