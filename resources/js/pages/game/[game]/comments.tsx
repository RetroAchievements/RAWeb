import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameCommentsMainRoot } from '@/features/comments/GameCommentsMainRoot';

const GameComments: AppPage<App.Community.Data.GameCommentsPageProps> = ({ game }) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Comments - {{gameTitle}}', { gameTitle: game.title })}
        description={`General discussion about the achievement set for ${game.title}`}
        ogImage={game.badgeUrl}
      />

      <AppLayout.Main>
        <GameCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameComments;
