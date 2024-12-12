import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameClaimsCommentsMainRoot } from '@/features/comments/GameClaimsCommentsMainRoot';

const GameClaimsComments: AppPage<App.Community.Data.GameClaimsCommentsPageProps> = ({ game }) => {
  const { t } = useTranslation();

  const metaDescription = `Internal discussion about claims for ${game.title}`;

  return (
    <>
      <Head title={t('Claim Comments - {{gameTitle}}', { gameTitle: game.title })}>
        <meta name="description" content={metaDescription} />
        <meta name="og:description" content={metaDescription} />

        <meta property="og:image" content={game.badgeUrl} />
        <meta property="og:type" content="retroachievements:comment-list" />
      </Head>

      <AppLayout.Main>
        <GameClaimsCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameClaimsComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameClaimsComments;
