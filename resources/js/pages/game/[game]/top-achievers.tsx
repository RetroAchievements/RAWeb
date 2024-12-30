import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { TopAchieversMainRoot } from '@/features/games/components/TopAchieversMainRoot';

const TopAchievers: AppPage<App.Platform.Data.GameTopAchieversPageProps> = ({ game }) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Top Achievers - {{gameTitle}}', { gameTitle: game.title })}
        description={`Top achievers for the achievement set for ${game.title}`}
        ogImage={game.badgeUrl}
      />

      <AppLayout.Main>
        <TopAchieversMainRoot />
      </AppLayout.Main>
    </>
  );
};

TopAchievers.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default TopAchievers;
