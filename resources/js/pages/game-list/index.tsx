import { Head } from '@inertiajs/react';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AllGamesMainRoot } from '@/features/game-list/components/AllGamesMainRoot';

const AllGames: AppPage = () => {
  return (
    <>
      <Head title="All Games">
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
