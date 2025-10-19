import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { RequestedGamesMainRoot } from '@/features/game-list/components/RequestedGamesMainRoot';
import { isCurrentlyPersistingViewAtom } from '@/features/game-list/state/game-list.atoms';

const UserRequestedGames: AppPage = () => {
  const { persistedViewPreferences, targetUser } =
    usePageProps<App.Platform.Data.GameListPageProps>();
  const { t } = useTranslation();

  useHydrateAtoms([
    [isCurrentlyPersistingViewAtom, !!persistedViewPreferences],
    //
  ]);

  return (
    <>
      <SEO
        title={t('Set Requests - {{user}}', { user: targetUser!.displayName })}
        description={`View ${targetUser!.displayName}'s requested achievement sets.`}
        ogImage={targetUser!.avatarUrl}
      />

      <div className="container">
        <AppLayout.Main>
          <RequestedGamesMainRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

UserRequestedGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserRequestedGames;
