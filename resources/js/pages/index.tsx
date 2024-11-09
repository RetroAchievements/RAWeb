import { Head } from '@inertiajs/react';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { HomeRoot } from '@/features/home/components/+root';
import { HomeSidebar } from '@/features/home/components/+sidebar';

const Home: AppPage = () => {
  return (
    <>
      <Head>
        <meta
          name="description"
          content="Earn and track achievements in classic games. RetroAchievements adds custom challenges to retro titles, letting you compete with others and revisit old favorites in new ways."
        />
      </Head>

      <div className="container">
        <AppLayout.Main>
          <HomeRoot />
        </AppLayout.Main>
      </div>

      <AppLayout.Sidebar>
        <HomeSidebar />
      </AppLayout.Sidebar>
    </>
  );
};

Home.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default Home;
