import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { HomeRoot } from '@/features/home/components/+root';
import { HomeSidebar } from '@/features/home/components/+sidebar';
import type { TranslatedString } from '@/types/i18next';

const Home: AppPage = () => {
  return (
    <>
      <SEO
        title={'RetroAchievements' as TranslatedString}
        description="Earn and track achievements in classic games. We add custom challenges to retro titles, letting you revisit old favorites in new ways."
      />

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
