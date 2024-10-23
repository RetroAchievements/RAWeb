import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameCommentsMainRoot } from '@/features/comments/GameCommentsMainRoot';

const GameComments: AppPage<App.Community.Data.GameCommentsPageProps> = ({ game }) => {
  const { t } = useLaravelReactI18n();

  return (
    <>
      <Head title={t('Comments - :gameTitle', { gameTitle: game.title })}>
        <meta
          name="description"
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
