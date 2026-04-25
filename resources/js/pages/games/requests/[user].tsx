import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { RequestedGamesMainRoot } from '@/features/game-list/components/RequestedGamesMainRoot';

const UserRequestedGames: AppPage = () => {
  const { targetUser } = usePageProps<App.Platform.Data.GameListPageProps>();
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Set Requests - {{user}}', { user: targetUser!.displayName })}
        description={`View ${targetUser!.displayName}'s requested achievement sets.`}
        ogImage={targetUser!.avatarUrl}
      />

      <AppLayout.Main>
        <RequestedGamesMainRoot />
      </AppLayout.Main>
    </>
  );
};

UserRequestedGames.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserRequestedGames;
