import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameHashesCommentsMainRoot } from '@/features/comments/GameHashesCommentsMainRoot';

const GameHashesComments: AppPage<App.Community.Data.GameHashesCommentsPageProps> = ({ game }) => {
  const { t } = useTranslation();

  const metaDescription = `Internal discussion about the hashes for ${game.title}`;

  return (
    <>
      <Head title={t('Hash Comments - {{gameTitle}}', { gameTitle: game.title })}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />

        <meta property="og:image" content={game.badgeUrl} />
        <meta property="og:type" content="retroachievements:comment-list" />
      </Head>

      <AppLayout.Main>
        <GameHashesCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameHashesComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameHashesComments;
