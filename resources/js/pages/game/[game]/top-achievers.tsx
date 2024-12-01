import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TopAchieversMainRoot } from '@/features/games/components/TopAchieversMainRoot';

const TopAchievers: AppPage<App.Platform.Data.GameTopAchieversPageProps> = ({ game }) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('Top Achievers - {{gameTitle}}', { gameTitle: game.title })}>
        <meta
          name="description"
          content={`Top achievers for the achievement set for ${game.title}`}
        />

        <meta property="og:image" content={game.badgeUrl} />
        <meta property="og:type" content="retroachievements:user-list" />
      </Head>

      <AppLayout.Main>
        <TopAchieversMainRoot />
      </AppLayout.Main>
    </>
  );
};

TopAchievers.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default TopAchievers;
