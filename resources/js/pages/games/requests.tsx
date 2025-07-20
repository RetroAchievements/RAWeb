import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { RequestedGamesMainRoot } from '@/features/game-list/components/RequestedGamesMainRoot';
import { isCurrentlyPersistingViewAtom } from '@/features/game-list/state/game-list.atoms';

const RequestedGames: AppPage = () => {
  const { persistedViewPreferences } = usePageProps<App.Platform.Data.GameListPageProps>();

  const { t } = useTranslation();

  useHydrateAtoms([
    [isCurrentlyPersistingViewAtom, !!persistedViewPreferences],
    //
  ]);

  return (
    <>
      <SEO
        title={t('Most Requested Sets')}
        description="Browse games awaiting achievement sets on RetroAchievements. View community requests, filter by system and development status, and find unclaimed games to develop."
      />

      <div className="container">
        <AppLayout.Main>
          <RequestedGamesMainRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

RequestedGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default RequestedGames;
