import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameModificationCommentsMainRoot } from '@/features/comments/GameModificationCommentsMainRoot';

const GameModificationComments: AppPage<App.Community.Data.GameClaimsCommentsPageProps> = ({
  game,
}) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('Modification Comments - {{gameTitle}}', { gameTitle: game.title })}>
        <meta
          name="description"
          content={`Internal discussion about game metadata modifications for ${game.title}`}
        />

        <meta property="og:image" content={game.badgeUrl} />
        <meta property="og:type" content="retroachievements:comment-list" />
      </Head>

      <AppLayout.Main>
        <GameModificationCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameModificationComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameModificationComments;
