import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { PageMetaDescription } from '@/common/components/PageMetaDescription';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameCommentsMainRoot } from '@/features/comments/GameCommentsMainRoot';

const GameComments: AppPage<App.Community.Data.GameCommentsPageProps> = ({ game }) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('Comments - {{gameTitle}}', { gameTitle: game.title })}>
        <PageMetaDescription
          content={`General discussion about the achievement set for ${game.title}`}
        />

        <meta property="og:image" content={game.badgeUrl} />
        <meta property="og:type" content="retroachievements:comment-list" />
      </Head>

      <AppLayout.Main>
        <GameCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameComments;
