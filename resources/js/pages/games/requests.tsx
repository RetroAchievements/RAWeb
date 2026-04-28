import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { RequestedGamesMainRoot } from '@/features/game-list/components/RequestedGamesMainRoot';

const RequestedGames: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Most Requested Sets')}
        description="Browse games awaiting achievement sets on RetroAchievements. View community requests, filter by system and development status, and find unclaimed games to develop."
      />

      <AppLayout.Main>
        <RequestedGamesMainRoot />
      </AppLayout.Main>
    </>
  );
};

RequestedGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default RequestedGames;
