import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { UserGameActivityMainRoot } from '@/features/user-game-activity/components/+root';

const UserGameActivity: AppPage = () => {
  const { player, game } = usePageProps<App.Platform.Data.PlayerGameActivityPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t("{{user}}'s activity for {{gameTitle}}", {
          user: player.displayName,
          gameTitle: game.title,
        })}
        description={`See an overview of ${player.displayName}'s history for ${game.title}`}
        ogImage={player.avatarUrl}
      />

      <AppLayout.Main>
        <UserGameActivityMainRoot />
      </AppLayout.Main>
    </>
  );
};

UserGameActivity.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default UserGameActivity;
