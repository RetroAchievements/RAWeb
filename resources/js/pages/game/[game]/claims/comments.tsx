import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { PageMetaDescription } from '@/common/components/PageMetaDescription';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameClaimsCommentsMainRoot } from '@/features/comments/GameClaimsCommentsMainRoot';

const GameClaimsComments: AppPage<App.Community.Data.GameClaimsCommentsPageProps> = ({ game }) => {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('Claim Comments - {{gameTitle}}', { gameTitle: game.title })}>
        <PageMetaDescription content={`Internal discussion about claims for ${game.title}`} />

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
